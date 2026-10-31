<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CryptoPrice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class CryptoController extends Controller
{
    public function prices(Request $request)
    {
        $prices = [];
        if (Schema::hasTable('crypto_prices')) {
            $builder = function () {
                return CryptoPrice::latest()->take(10)->get()->map(fn ($p) => [
                    'symbol'             => $p->symbol,
                    'name'               => $p->name,
                    'price_kes'          => (float) $p->price_kes,
                    'price_usd'          => (float) $p->price_usd,
                    'percent_change_24h' => (float) ($p->percent_change_24h ?? 0),
                    'last_updated'       => $p->last_updated ?? $p->updated_at,
                ])->values()->toArray();
            };
            try {
                $prices = Cache::remember('crypto_prices_api', 60, $builder);
            } catch (\Throwable $e) {
                try { $prices = $builder(); } catch (\Throwable $e2) { $prices = []; }
            }
        }
        return response()->json(['success' => true, 'data' => $prices, 'prices' => $prices]);
    }

    public function history(Request $request, $symbol)
    {
        $days = (int) $request->get('days', 30);
        $history = [];
        if (Schema::hasTable('crypto_prices')) {
            try {
                $history = CryptoPrice::where('symbol', $symbol)
                    ->orderBy('created_at', 'desc')->take($days)->get()
                    ->map(fn ($p) => [
                        'price_kes'  => (float) $p->price_kes,
                        'price_usd'  => (float) $p->price_usd,
                        'created_at' => $p->created_at,
                    ])->values()->toArray();
            } catch (\Throwable $e) {}
        }
        return response()->json(['success' => true, 'symbol' => $symbol, 'data' => $history]);
    }

    public function marketData()
    {
        $top = [];
        if (Schema::hasTable('crypto_prices')) {
            try {
                $top = CryptoPrice::latest()->take(5)->get()->map(fn ($p) => [
                    'symbol'     => $p->symbol,
                    'price_kes'  => (float) $p->price_kes,
                    'price_usd'  => (float) $p->price_usd,
                    'change_24h' => (float) ($p->percent_change_24h ?? 0),
                ])->values()->toArray();
            } catch (\Throwable $e) {}
        }
        return response()->json(['success' => true, 'data' => ['top_cryptos' => $top]]);
    }
}
