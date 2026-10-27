<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TradingAccount;
use App\Models\TradingPair;
use App\Services\Trading\ChartService;
use App\Services\Trading\TradingEngine;
use App\Services\Trading\WalletTradingBridge;
use Illuminate\Http\Request;

class TradingController extends Controller
{
    protected TradingEngine $engine;
    protected ChartService $chart;
    protected WalletTradingBridge $bridge;
    protected ?TradingPair $pair = null;

    public function __construct()
    {
        $this->pair = TradingPair::where('symbol', 'BTCUSDT')->first();
        $this->engine = new TradingEngine($this->pair ?? new TradingPair());
        $this->chart  = new ChartService();
        $this->bridge = new WalletTradingBridge();
    }

    public function balance(Request $request)
    {
        $user = $request->user();
        $account = $user->tradingAccount;

        return response()->json([
            'success'         => true,
            'trading_balance' => (float) ($account?->balance ?? 0),
            'btc_balance'     => (float) ($account?->btc_balance ?? 0),
            'wallet_balance'  => (float) ($user->wallet?->balance ?? 0),
        ]);
    }

    public function price()
    {
        return response()->json([
            'success'   => true,
            'price_kes' => $this->engine->getMarketPrice(),
        ]);
    }

    public function candles(Request $request, string $interval = '1h')
    {
        if (!$this->pair) {
            return response()->json(['success' => false, 'message' => 'Trading pair not configured'], 500);
        }

        $allowed = ['1m', '5m', '15m', '1h', '4h', '1d'];
        if (!in_array($interval, $allowed, true)) {
            $interval = '1h';
        }

        try {
            $candles = $this->chart->getCandles($this->pair->id, $interval, 500);
            return response()->json(['success' => true, 'data' => $candles]);
        } catch (\Throwable $e) {
            \Log::error('API candles failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to load candles'], 500);
        }
    }

    public function orderBook()
    {
        return response()->json(['success' => true, 'data' => $this->engine->getOrderBook()]);
    }

    public function transferToTrading(Request $request)
    {
        $request->validate(['amount' => 'required|numeric|min:1']);

        try {
            $this->bridge->walletToTrading($request->user(), (float) $request->amount);
            return response()->json(['success' => true, 'message' => 'Transfer complete']);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function transferFromTrading(Request $request)
    {
        $request->validate(['amount' => 'required|numeric|min:1']);

        try {
            $this->bridge->tradingToWallet($request->user(), (float) $request->amount);
            return response()->json(['success' => true, 'message' => 'Transfer complete']);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function buy(Request $request)
    {
        $request->validate([
            'amount_btc'  => 'required|numeric|min:0.0001',
            'order_type'  => 'required|in:market,limit',
            'limit_price' => 'required_if:order_type,limit|nullable|numeric|min:0',
        ]);

        try {
            $order = $this->engine->placeOrder(
                $request->user(), 'buy', $request->order_type,
                (float) $request->amount_btc,
                $request->limit_price ? (float) $request->limit_price : null
            );
            return response()->json(['success' => true, 'order' => $order]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function sell(Request $request)
    {
        $request->validate([
            'amount_btc'  => 'required|numeric|min:0.0001',
            'order_type'  => 'required|in:market,limit',
            'limit_price' => 'required_if:order_type,limit|nullable|numeric|min:0',
        ]);

        try {
            $order = $this->engine->placeOrder(
                $request->user(), 'sell', $request->order_type,
                (float) $request->amount_btc,
                $request->limit_price ? (float) $request->limit_price : null
            );
            return response()->json(['success' => true, 'order' => $order]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function orders(Request $request)
    {
        $orders = $request->user()->tradeOrders()->latest()->take(50)->get();
        return response()->json(['success' => true, 'data' => $orders]);
    }
}
