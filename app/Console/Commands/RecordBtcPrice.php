<?php

namespace App\Console\Commands;

use App\Events\BtcPriceUpdated;
use App\Models\BtcPriceHistory;
use App\Models\TradingPair;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RecordBtcPrice extends Command
{
    protected $signature = 'trading:record-btc-price';
    protected $description = 'Fetch live BTC price and record it';

    public function handle(): int
    {
        $price = $this->fetchBinancePrice();

        if (!$price) {
            $this->warn('Binance API unavailable — trying fallback');
            $price = $this->fallbackPrice();
        }

        if (!$price) {
            $this->error('❌ No price source available');
            return self::FAILURE;
        }

        BtcPriceHistory::create([
            'price'       => $price['usd'],
            'price_kes'   => $price['kes'],
            'currency'    => 'USD',
            'recorded_at' => now(),
        ]);

        Cache::put('btc_market_price', $price['kes'], 60);
        Cache::put('btc_market_price_usd', $price['usd'], 60);

        try {
            event(new BtcPriceUpdated($price['usd'], $price['kes']));
        } catch (\Throwable $e) {
            Log::warning('BtcPriceUpdated broadcast failed', ['error' => $e->getMessage()]);
        }

        $this->info(sprintf(
            '✅ BTC price recorded: $%s USD · KES %s',
            number_format($price['usd'], 2),
            number_format($price['kes'], 2)
        ));

        return self::SUCCESS;
    }

    protected function fetchBinancePrice(): ?array
    {
        try {
            $res = Http::timeout(5)->get('https://api.binance.com/api/v3/ticker/price', [
                'symbol' => 'BTCUSDT',
            ]);

            if (!$res->successful()) {
                Log::warning('Binance ticker failed', ['status' => $res->status()]);
                return null;
            }

            $usd = (float) ($res->json('price') ?? 0);
            if ($usd <= 0) return null;

            $rate = (float) Cache::get('usd_kes_rate', 0);
            if ($rate <= 0) {
                $rate = $this->fetchUsdKesRate();
            }

            return [
                'usd' => round($usd, 2),
                'kes' => round($usd * $rate, 2),
            ];
        } catch (\Throwable $e) {
            Log::error('RecordBtcPrice fetch failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    protected function fetchUsdKesRate(): float
    {
        try {
            $res = Http::timeout(5)->get('https://api.exchangerate-api.com/v4/latest/USD');
            if ($res->successful()) {
                $rate = (float) ($res->json('rates.KES') ?? 0);
                if ($rate > 0) {
                    Cache::put('usd_kes_rate', $rate, 3600);
                    return $rate;
                }
            }
        } catch (\Throwable $e) {
            Log::warning('USD/KES rate fetch failed', ['error' => $e->getMessage()]);
        }
        return 130.0;
    }

    protected function fallbackPrice(): ?array
    {
        $last = BtcPriceHistory::latest('recorded_at')->first();
        if ($last) {
            return [
                'usd' => (float) $last->price,
                'kes' => (float) ($last->price_kes ?? $last->price * 130),
            ];
        }

        $pair = TradingPair::where('symbol', 'BTCUSDT')->first();
        if ($pair) {
            $close = $pair->candles()->orderBy('open_time', 'desc')->value('close');
            if ($close) {
                return ['usd' => (float) $close, 'kes' => (float) $close * 130];
            }
        }

        return null;
    }
}
