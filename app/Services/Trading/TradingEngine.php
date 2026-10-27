<?php

namespace App\Services\Trading;

use App\Models\BtcPriceHistory;
use App\Models\TradeOrder;
use App\Models\TradingAccount;
use App\Models\TradingPair;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class TradingEngine
{
    /** Float comparison epsilon (BTC / KES arithmetic). */
    private const EPS = 1e-9;

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
                'amount' => max(0.0, (float) $o->amount_btc - (float) $o->filled_amount),
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
                'amount' => max(0.0, (float) $o->amount_btc - (float) $o->filled_amount),
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

        $min = (float) ($this->pair->min_trade_amount ?? 0);
        $max = (float) ($this->pair->max_trade_amount ?? 0);
        if ($min > 0 && $amountBtc < $min) {
            throw new RuntimeException("Minimum trade amount is {$min} BTC.");
        }
        if ($max > 0 && $amountBtc > $max) {
            throw new RuntimeException("Maximum trade amount is {$max} BTC.");
        }
        if ($orderType === 'limit' && ($limitPrice === null || $limitPrice <= 0)) {
            throw new RuntimeException('Limit orders require a positive limit price.');
        }

        $marketPrice = $this->getMarketPrice();
        $executionPrice = $orderType === 'market' ? $marketPrice : (float) $limitPrice;
        $kesTotal = round($executionPrice * $amountBtc, 2);

        return DB::transaction(function () use (
            $user, $side, $orderType, $amountBtc, $limitPrice,
            $stopPrice, $takeProfit, $stopLoss, $timeInForce,
            $kesTotal, $marketPrice
        ) {
            $account = TradingAccount::lockForUpdate()->firstOrCreate(
                ['user_id' => $user->id],
                ['balance' => 0, 'locked_balance' => 0, 'btc_balance' => 0]
            );

            if ($side === 'buy') {
                // "balance" == total KES. "locked_balance" == held. Available == balance - locked.
                $available = (float) $account->balance - (float) $account->locked_balance;
                if ($available + self::EPS < $kesTotal) {
                    throw new RuntimeException('Insufficient available KES balance.');
                }
                $account->locked_balance = (float) $account->locked_balance + $kesTotal;
            } else {
                if ((float) $account->btc_balance + self::EPS < $amountBtc) {
                    throw new RuntimeException('Insufficient BTC balance.');
                }
                $account->btc_balance = (float) $account->btc_balance - $amountBtc;
            }
            $account->save();

            $order = TradeOrder::create([
                'user_id'       => $user->id,
                'pair_id'       => $this->pair->id,
                'side'          => $side,
                'order_type'    => $orderType,
                'amount_btc'    => $amountBtc,
                'filled_amount' => 0,
                'limit_price'   => $limitPrice,
                'stop_price'    => $stopPrice,
                'price_per_btc' => $marketPrice,
                'filled_kes'    => 0,
                'status'        => 'pending',
                'take_profit'   => $takeProfit,
                'stop_loss'     => $stopLoss,
                'time_in_force' => $timeInForce,
            ]);

            if ($orderType === 'market') {
                // BUG E fix: pin the price we already locked against.
                $this->executeOrder($order, $marketPrice);
            }

            return $order->fresh();
        });
    }

    /* ============================================================
     | ORDER EXECUTION (market orders / manual fill)
     ============================================================ */

    public function executeOrder(TradeOrder $order, ?float $executionPrice = null): TradeOrder
    {
        // BUG F fix: refuse to settle an order more than once.
        $order->refresh();
        if (!in_array($order->status, ['pending', 'partial'], true)) {
            return $order;
        }

        $remaining = (float) $order->amount_btc - (float) $order->filled_amount;
        if ($remaining <= self::EPS) {
            if ($order->status !== 'completed') {
                $order->update(['status' => 'completed']);
            }
            return $order->fresh();
        }

        $price = $executionPrice ?? (float) ($order->limit_price ?? $this->getMarketPrice());
        if ($price <= 0) {
            throw new RuntimeException('Cannot execute order with non-positive price.');
        }
        $kesTotal = round($price * $remaining, 2);

        return DB::transaction(function () use ($order, $price, $kesTotal, $remaining) {
            $locked = TradeOrder::lockForUpdate()->find($order->id);
            if (!$locked || !in_array($locked->status, ['pending', 'partial'], true)) {
                return $locked ? $locked->fresh() : $order->fresh();
            }

            $account = TradingAccount::lockForUpdate()->firstOrCreate(
                ['user_id' => $locked->user_id],
                ['balance' => 0, 'locked_balance' => 0, 'btc_balance' => 0]
            );

            if ($locked->side === 'buy') {
                // Total drops by kesTotal; held drops by kesTotal; BTC credited.
                $account->balance        = max(0.0, (float) $account->balance - $kesTotal);
                $account->locked_balance = max(0.0, (float) $account->locked_balance - $kesTotal);
                $account->btc_balance    = (float) $account->btc_balance + $remaining;
            } else {
                $account->balance = (float) $account->balance + $kesTotal;
            }
            $account->save();

            $locked->filled_amount = (float) $locked->filled_amount + $remaining;
            $locked->filled_kes    = (float) $locked->filled_kes + $kesTotal;
            $locked->price_per_btc = $price;
            $locked->status        = 'completed';
            $locked->save();

            $this->recordTradeTransaction(
                $locked,
                $locked->side === 'buy' ? 'trade_buy' : 'trade_sell',
                $kesTotal,
                (float) $account->balance,
                $price,
                $remaining
            );

            Log::info('Trade executed', [
                'order_id' => $locked->id,
                'price'    => $price,
                'btc'      => $remaining,
                'kes'      => $kesTotal,
                'side'     => $locked->side,
            ]);

            return $locked->fresh();
        });
    }

    /* ============================================================
     | ORDER CANCELLATION (partial-fill aware)
     ============================================================ */

    public function cancelOrder(TradeOrder $order): TradeOrder
    {
        $order->refresh();
        if (!in_array($order->status, ['pending', 'partial'], true)) {
            throw new RuntimeException('Order cannot be cancelled.');
        }

        return DB::transaction(function () use ($order) {
            $locked = TradeOrder::lockForUpdate()->find($order->id);
            if (!$locked || !in_array($locked->status, ['pending', 'partial'], true)) {
                throw new RuntimeException('Order cannot be cancelled.');
            }

            $remaining = (float) $locked->amount_btc - (float) $locked->filled_amount;

            $account = TradingAccount::lockForUpdate()->firstOrCreate(
                ['user_id' => $locked->user_id],
                ['balance' => 0, 'locked_balance' => 0, 'btc_balance' => 0]
            );

            if ($remaining > self::EPS) {
                if ($locked->side === 'buy') {
                    // BUG B fix: refund only the still-locked portion.
                    $unitPrice = (float) ($locked->limit_price ?? $locked->price_per_btc ?? 0);
                    $refund = round($unitPrice * $remaining, 2);
                    $account->locked_balance = max(0.0, (float) $account->locked_balance - $refund);
                } else {
                    // Return the unsold BTC.
                    $account->btc_balance = (float) $account->btc_balance + $remaining;
                }
                $account->save();
            }

            $locked->update(['status' => 'cancelled']);

            return $locked->fresh();
        });
    }

    /* ============================================================
     | MATCHING (limit order book)
     ============================================================ */

    public function matchOrder(TradeOrder $order): void
    {
        // BUG C fix: refresh from DB before doing anything. MatchOrders'
        // outer loop iterates a snapshot; without this the same order can
        // be filled twice within one run.
        $order->refresh();
        if (!in_array($order->status, ['pending', 'partial'], true)) {
            return;
        }
        if ((float) $order->amount_btc - (float) $order->filled_amount <= self::EPS) {
            return;
        }

        $opposite = $order->side === 'buy' ? 'sell' : 'buy';

        $counterparties = TradeOrder::where('pair_id', $order->pair_id)
            ->where('side', $opposite)
            ->where('user_id', '!=', $order->user_id)   // no self-trades
            ->whereIn('status', ['pending', 'partial'])
            ->orderByRaw($order->side === 'buy' ? 'limit_price ASC' : 'limit_price DESC')
            ->lockForUpdate()
            ->get();

        foreach ($counterparties as $counter) {
            $order->refresh();
            if ((float) $order->filled_amount + self::EPS >= (float) $order->amount_btc) {
                break;
            }
            if (!in_array($order->status, ['pending', 'partial'], true)) {
                break;
            }

            $counter->refresh();
            if (!in_array($counter->status, ['pending', 'partial'], true)) {
                continue;
            }

            $remainingTaker = (float) $order->amount_btc - (float) $order->filled_amount;
            $remainingMaker = (float) $counter->amount_btc - (float) $counter->filled_amount;
            if ($remainingTaker <= self::EPS || $remainingMaker <= self::EPS) {
                continue;
            }

            $fill = min($remainingMaker, $remainingTaker);

            $tradePrice = (float) (
                $counter->limit_price
                ?: $order->limit_price
                ?: $this->getMarketPrice()
            );
            if ($tradePrice <= 0) {
                continue;
            }
            $kesValue = round($tradePrice * $fill, 2);

            DB::transaction(function () use ($order, $counter, $fill, $kesValue, $tradePrice) {
                // Lock fresh copies — order objects in memory could be stale.
                $taker = TradeOrder::lockForUpdate()->find($order->id);
                $maker = TradeOrder::lockForUpdate()->find($counter->id);

                if (!$taker || !$maker) {
                    return;
                }
                if (!in_array($taker->status, ['pending', 'partial'], true)) {
                    return;
                }
                if (!in_array($maker->status, ['pending', 'partial'], true)) {
                    return;
                }

                $takerRemaining = (float) $taker->amount_btc - (float) $taker->filled_amount;
                $makerRemaining = (float) $maker->amount_btc - (float) $maker->filled_amount;
                if ($takerRemaining <= self::EPS || $makerRemaining <= self::EPS) {
                    return;
                }

                $actualFill = min($makerRemaining, $takerRemaining);
                $actualKes  = round($tradePrice * $actualFill, 2);

                // Who is buyer, who is seller?
                if ($taker->side === 'buy') {
                    [$buyerOrder, $sellerOrder] = [$taker, $maker];
                } else {
                    [$buyerOrder, $sellerOrder] = [$maker, $taker];
                }

                // Update both order rows first.
                foreach ([$taker, $maker] as $o) {
                    $o->filled_amount = (float) $o->filled_amount + $actualFill;
                    $o->filled_kes    = (float) $o->filled_kes + $actualKes;
                    $o->price_per_btc = $tradePrice;
                    $o->status = ((float) $o->filled_amount + self::EPS) >= (float) $o->amount_btc
                        ? 'completed'
                        : 'partial';
                    $o->save();
                }

                // BUG A fix: settle accounts.
                $buyer = TradingAccount::lockForUpdate()->firstOrCreate(
                    ['user_id' => $buyerOrder->user_id],
                    ['balance' => 0, 'locked_balance' => 0, 'btc_balance' => 0]
                );
                $buyer->balance        = max(0.0, (float) $buyer->balance - $actualKes);
                $buyer->locked_balance = max(0.0, (float) $buyer->locked_balance - $actualKes);
                $buyer->btc_balance    = (float) $buyer->btc_balance + $actualFill;
                $buyer->save();

                $seller = TradingAccount::lockForUpdate()->firstOrCreate(
                    ['user_id' => $sellerOrder->user_id],
                    ['balance' => 0, 'locked_balance' => 0, 'btc_balance' => 0]
                );
                $seller->balance = (float) $seller->balance + $actualKes;
                $seller->save();

                // BUG H fix: ledger rows for both sides.
                $this->recordTradeTransaction(
                    $buyerOrder, 'trade_buy', $actualKes,
                    (float) $buyer->balance, $tradePrice, $actualFill
                );
                $this->recordTradeTransaction(
                    $sellerOrder, 'trade_sell', $actualKes,
                    (float) $seller->balance, $tradePrice, $actualFill
                );

                Log::info('Limit order matched', [
                    'taker_order' => $taker->id,
                    'maker_order' => $maker->id,
                    'price'       => $tradePrice,
                    'btc'         => $actualFill,
                    'kes'         => $actualKes,
                ]);
            });
        }

        $order->refresh();
    }

    /* ============================================================
     | LEDGER HELPER
     ============================================================ */

    /**
     * BUG D fix: never write wallet_id = 0 (FK violation on Postgres).
     * Uses the user's wallet; creates one if missing; skips the ledger
     * row with a warning rather than crash.
     */
    protected function recordTradeTransaction(
        TradeOrder $order,
        string $type,
        float $amountKes,
        float $balanceAfter,
        float $price,
        float $fillBtc
    ): void {
        $user = $order->user;
        if (!$user) {
            Log::warning('Trade settled but user missing; skipping Transaction', ['order_id' => $order->id]);
            return;
        }

        $wallet = $user->wallet;
        if (!$wallet) {
            $wallet = Wallet::firstOrCreate(['user_id' => $user->id]);
        }

        $walletId = $wallet?->id;
        if (!$walletId) {
            Log::warning('Trade settled but wallet missing; skipping Transaction', [
                'order_id' => $order->id,
                'user_id'  => $user->id,
            ]);
            return;
        }

        Transaction::create([
            'user_id'       => $user->id,
            'wallet_id'     => $walletId,
            'type'          => $type,
            'amount'        => $amountKes,
            'balance_after' => $balanceAfter,
            'description'   => sprintf(
                '%s %.8F BTC @ KES %.2F (order #%d)',
                $type === 'trade_buy' ? 'BOUGHT' : 'SOLD',
                $fillBtc,
                $price,
                $order->id
            ),
            'reference'     => 'TRD-' . $order->id,
            'status'        => 'completed',
        ]);
    }
}
