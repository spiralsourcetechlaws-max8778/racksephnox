<?php

namespace App\Http\Controllers;

use App\Models\TradeOrder;
use App\Models\TradingAccount;
use App\Models\TradingPair;
use App\Services\Trading\ChartService;
use App\Services\Trading\TradingEngine;
use App\Services\Trading\WalletTradingBridge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class TradingController extends Controller
{
    protected TradingEngine $engine;
    protected TradingPair $pair;
    protected ChartService $chart;
    protected WalletTradingBridge $bridge;

    public function __construct()
    {
        $this->pair = TradingPair::firstOrCreate(
            ['symbol' => 'BTCUSDT'],
            [
                'base_currency'    => 'BTC',
                'quote_currency'   => 'USDT',
                'min_trade_amount' => 0.0001,
                'max_trade_amount' => 100,
                'tick_size'        => 0.0001,
                'is_active'        => 1,
            ]
        );

        $this->engine = new TradingEngine($this->pair);
        $this->chart  = new ChartService();
        $this->bridge = new WalletTradingBridge();
    }

    public function index()
    {
        $user = Auth::user();

        $tradingAccount = $user->tradingAccount ?? TradingAccount::create([
            'user_id'        => $user->id,
            'balance'        => 0,
            'locked_balance' => 0,
            'btc_balance'    => 0,
        ]);

        $btcPrice = Cache::remember('btc_market_price', 30, fn () => $this->engine->getMarketPrice());

        $openOrders = TradeOrder::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'partial'])
            ->latest()
            ->get();

        $orderHistory = TradeOrder::where('user_id', $user->id)
            ->where('status', 'completed')
            ->latest()
            ->take(20)
            ->get();

        $orderBook = $this->engine->getOrderBook();
        $walletBalance = (float) ($user->wallet?->balance ?? 0);

        return view('trading.index', compact(
            'tradingAccount', 'btcPrice', 'openOrders', 'orderHistory', 'orderBook', 'walletBalance'
        ));
    }

    public function transferToTrading(Request $request)
    {
        $request->validate(['amount' => 'required|numeric|min:1']);

        try {
            $this->bridge->walletToTrading(Auth::user(), (float) $request->amount);
            return back()->with('success', 'KES ' . number_format($request->amount, 2) . ' transferred to trading account.');
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function transferFromTrading(Request $request)
    {
        $request->validate(['amount' => 'required|numeric|min:1']);

        try {
            $this->bridge->tradingToWallet(Auth::user(), (float) $request->amount);
            return back()->with('success', 'KES ' . number_format($request->amount, 2) . ' transferred to wallet.');
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function buy(Request $request)
    {
        $request->validate([
            'amount_btc'  => 'required|numeric|min:' . $this->pair->min_trade_amount,
            'order_type'  => 'required|in:market,limit,stop',
            'price'       => 'required_if:order_type,limit,stop|nullable|numeric|min:0',
            'stop_price'  => 'nullable|numeric|min:0',
            'take_profit' => 'nullable|numeric|min:0',
            'stop_loss'   => 'nullable|numeric|min:0',
        ]);

        try {
            $order = $this->engine->placeOrder(
                Auth::user(), 'buy', $request->order_type,
                (float) $request->amount_btc,
                $request->price ? (float) $request->price : null,
                $request->stop_price ? (float) $request->stop_price : null,
                $request->take_profit ? (float) $request->take_profit : null,
                $request->stop_loss ? (float) $request->stop_loss : null,
                $request->time_in_force ?? 'GTC'
            );
            return back()->with('success', "Buy order placed. Order #{$order->id}");
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function sell(Request $request)
    {
        $request->validate([
            'amount_btc'  => 'required|numeric|min:' . $this->pair->min_trade_amount,
            'order_type'  => 'required|in:market,limit,stop',
            'price'       => 'required_if:order_type,limit,stop|nullable|numeric|min:0',
            'stop_price'  => 'nullable|numeric|min:0',
            'take_profit' => 'nullable|numeric|min:0',
            'stop_loss'   => 'nullable|numeric|min:0',
        ]);

        try {
            $order = $this->engine->placeOrder(
                Auth::user(), 'sell', $request->order_type,
                (float) $request->amount_btc,
                $request->price ? (float) $request->price : null,
                $request->stop_price ? (float) $request->stop_price : null,
                $request->take_profit ? (float) $request->take_profit : null,
                $request->stop_loss ? (float) $request->stop_loss : null
            );
            return back()->with('success', "Sell order placed. Order #{$order->id}");
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function cancelOrder(TradeOrder $order)
    {
        if ($order->user_id !== Auth::id() || !in_array($order->status, ['pending', 'partial'])) {
            return back()->withErrors(['error' => 'Cannot cancel this order.']);
        }
        try {
            $this->engine->cancelOrder($order);
            return back()->with('success', 'Order cancelled.');
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function orderBook()
    {
        return response()->json($this->engine->getOrderBook());
    }

    public function candles(Request $request, string $interval = '1h')
    {
        $allowed = ['1m', '5m', '15m', '1h', '4h', '1d'];
        if (!in_array($interval, $allowed, true)) {
            $interval = '1h';
        }

        try {
            $candles = $this->chart->getCandles($this->pair->id, $interval, 500);
            return response()->json($candles);
        } catch (\Throwable $e) {
            \Log::error('Candles fetch failed', ['interval' => $interval, 'error' => $e->getMessage()]);
            return response()->json([], 500);
        }
    }
}
