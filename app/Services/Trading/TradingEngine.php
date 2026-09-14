<?php

namespace App\Services\Trading;

use App\Models\TradeOrder;
use App\Models\TradingAccount;
use App\Models\TradingPair;
use App\Models\BtcPriceHistory;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class TradingEngine
{
    protected TradingPair $pair;

    public function __construct(TradingPair $pair)
    {
        $this->pair = $pair;
    }

    /* ============================================================
     | MARKET DATA
     ============================================================ */

    public function getMarketPrice(): float
    {
        return (float) Cache::remember("market_price_{$this->pair->symbol}", 30, function () {
            $latest = BtcPriceHistory::latest('recorded_at')->first();
            if ($latest && $latest->price_kes) {
                return (float) $latest->price_kes;
            }
            $close = $this->pair->candles()
                ->orderBy('open_time', 'desc')
                ->value('close');
            return (float) ($close ?? 5_000_000);
        });
    }

    public function getOrderBook(int $depth = 20): array
    {
        $asks = TradeOrder::where('pair_id', $this->pair->id)
            ->where('side', 'sell')
            ->whereIn('status', ['pending', 'partial'])
            ->orderBy('limit_price', 'asc')
            ->limit($depth)
            ->get()
            ->map(fn ($o) => [
                'price'  => (float) ($o->limit_price ?? 0),
                'amount' => (float) ($o->amount_btc - ($o->filled_amount ?? 0)),
            ])
            ->values()
            ->toArray();

        $bids = TradeOrder::where('pair_id', $this->pair->id)
            ->where('side', 'buy')
            ->whereIn('status', ['pending', 'partial'])
            ->orderBy('limit_price', 'desc')
            ->limit($depth)
            ->get()
            ->map(fn ($o) => [
                'price'  => (float) ($o->limit_price ?? 0),
                'amount' => (float) ($o->amount_btc - ($o->filled_amount ?? 0)),
            ])
            ->values()
            ->toArray();

        return ['asks' => $asks, 'bids' => $bids];
    }

    /* ============================================================
     | ORDER PLACEMENT
     ============================================================ */

    public function placeOrder(
        User $user,
        string $side,
        string $orderType,
        float $amountBtc,
        ?float $limitPrice = null,
        ?float $stopPrice = null,
        ?float $takeProfit = null,
        ?float $stopLoss = null,
        string $timeInForce = 'GTC'
    ): TradeOrder {
        if (!in_array($side, ['buy', 'sell'], true)) {
            throw new RuntimeException('Invalid order side.');
        }
        if ($amountBtc <= 0) {
            throw new RuntimeException('Amount must be greater than zero.');
        }
        if ($amountBtc < $this->pair->min_trade_amount) {
            throw new RuntimeException("Minimum trade amount is {$this->pair->min_trade_amount} BTC.");
        }
        if ($amountBtc > $this->pair->max_trade_amount) {
            throw new RuntimeException("Maximum trade amount is {$this->pair->max_trade_amount} BTC.");
        }

        $marketPrice = $this->getMarketPrice();
        $executionPrice = $orderType === 'market' ? $marketPrice : (float) $limitPrice;
        $kesTotal = round($executionPrice * $amountBtc, 2);

        return DB::transaction(function () use ($user, $side, $orderType, $amountBtc, $limitPrice, $stopPrice, $takeProfit, $stopLoss, $timeInForce, $kesTotal, $marketPrice) {

            $account = TradingAccount::lockForUpdate()->firstOrCreate(
                ['user_id' => $user->id],
                ['balance' => 0, 'locked_balance' => 0, 'btc_balance' => 0]
            );

            if ($side === 'buy') {
                if ($account->balance < $kesTotal) {
                    throw new RuntimeException('Insufficient KES balance.');
                }
                $account->balance -= $kesTotal;
                $account->locked_balance += $kesTotal;
            } else {
                if ($account->btc_balance < $amountBtc) {
                    throw new RuntimeException('Insufficient BTC balance.');
                }
                $account->btc_balance -= $amountBtc;
            }
            $account->save();

            $order = TradeOrder::create([
                'user_id'        => $user->id,
                'pair_id'        => $this->pair->id,
                'side'           => $side,
                'order_type'     => $orderType,
                'amount_btc'     => $amountBtc,
                'filled_amount'  => 0,
                'limit_price'    => $limitPrice,
                'stop_price'     => $stopPrice,
                'price_per_btc'  => $marketPrice,
                'filled_kes'     => 0,
                'status'         => 'pending',
                'take_profit'    => $takeProfit,
                'stop_loss'      => $stopLoss,
                'time_in_force'  => $timeInForce,
            ]);

            if ($orderType === 'market') {
                $this->executeOrder($order);
            }

            return $order->fresh();
        });
    }

    /* ============================================================
     | ORDER EXECUTION
     ============================================================ */

    public function executeOrder(TradeOrder $order, ?float $executionPrice = null): TradeOrder
    {
        $price = $executionPrice ?? (float) ($order->limit_price ?? $this->getMarketPrice());
        $kesTotal = round($price * $order->amount_btc, 2);

        return DB::transaction(function () use ($order, $price, $kesTotal) {

            $account = TradingAccount::lockForUpdate()->firstOrCreate(
                ['user_id' => $order->user_id],
                ['balance' => 0, 'locked_balance' => 0, 'btc_balance' => 0]
            );

            if ($order->side === 'buy') {
                $account->locked_balance = max(0, $account->locked_balance - $kesTotal);
                $account->btc_balance += $order->amount_btc;
            } else {
                $account->balance += $kesTotal;
            }
            $account->save();

            $order->update([
                'status'        => 'completed',
                'filled_amount' => $order->amount_btc,
                'filled_kes'    => $kesTotal,
                'price_per_btc' => $price,
            ]);

            Transaction::create([
                'user_id'       => $order->user_id,
                'wallet_id'     => optional($order->user->wallet)->id ?? 0,
                'type'          => $order->side === 'buy' ? 'trade_buy' : 'trade_sell',
                'amount'        => $kesTotal,
                'balance_after' => $account->balance,
                'description'   => strtoupper($order->side) . " {$order->amount_btc} BTC @ KES {$price}",
                'reference'     => 'TRD-' . $order->id,
                'status'        => 'completed',
            ]);

            Log::info('Trade executed', ['order_id' => $order->id, 'price' => $price]);

            return $order->fresh();
        });
    }

    public function cancelOrder(TradeOrder $order): TradeOrder
    {
        if (!in_array($order->status, ['pending', 'partial'], true)) {
            throw new RuntimeException('Order cannot be cancelled.');
        }

        return DB::transaction(function () use ($order) {
            $account = TradingAccount::lockForUpdate()->firstOrCreate(
                ['user_id' => $order->user_id],
                ['balance' => 0, 'locked_balance' => 0, 'btc_balance' => 0]
            );

            if ($order->side === 'buy') {
                $refund = (float) (($order->limit_price ?? 0) * $order->amount_btc);
                $account->locked_balance = max(0, $account->locked_balance - $refund);
                $account->balance += $refund;
            } else {
                $account->btc_balance += $order->amount_btc;
            }
            $account->save();

            $order->update(['status' => 'cancelled']);

            return $order->fresh();
        });
    }

    /* ============================================================
     | MATCHING
     ============================================================ */

    public function matchOrder(TradeOrder $order): void
    {
        $opposite = $order->side === 'buy' ? 'sell' : 'buy';

        $counterparties = TradeOrder::where('pair_id', $this->pair->id)
            ->where('side', $opposite)
            ->whereIn('status', ['pending', 'partial'])
            ->orderByRaw($order->side === 'buy' ? 'limit_price ASC' : 'limit_price DESC')
            ->get();

        foreach ($counterparties as $counter) {
            if ($order->filled_amount >= $order->amount_btc) break;

            $remainingMaker = $counter->amount_btc - $counter->filled_amount;
            $remainingTaker = $order->amount_btc - $order->filled_amount;
            $fill = min($remainingMaker, $remainingTaker);

            $tradePrice = $counter->limit_price ?: $order->limit_price ?: $this->getMarketPrice();
            $kesValue = round($tradePrice * $fill, 2);

            DB::transaction(function () use ($order, $counter, $fill, $kesValue, $tradePrice) {
                $order->filled_amount += $fill;
                $order->filled_kes    += $kesValue;
                $order->price_per_btc  = $tradePrice;
                $order->status         = $order->filled_amount >= $order->amount_btc ? 'completed' : 'partial';
                $order->save();

                $counter->filled_amount += $fill;
                $counter->filled_kes    += $kesValue;
                $counter->status        = $counter->filled_amount >= $counter->amount_btc ? 'completed' : 'partial';
                $counter->save();
            });
        }
    }
}
