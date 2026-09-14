<?php

namespace App\Services\Trading;

use App\Models\TradingCandle;
use App\Models\TradingPair;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChartService
{
    public function getCandles(int $pairId, string $interval = '1h', int $limit = 500)
    {
        $candles = TradingCandle::where('pair_id', $pairId)
            ->where('interval', $interval)
            ->orderBy('open_time')
            ->limit($limit)
            ->get();

        if ($candles->count() >= 10) return $candles;

        $pair = TradingPair::find($pairId);
        if ($pair) {
            $this->syncHistoricalData($pair, $interval, $limit);
            $candles = TradingCandle::where('pair_id', $pairId)
                ->where('interval', $interval)
                ->orderBy('open_time')
                ->limit($limit)
                ->get();

            if ($candles->count() >= 10) return $candles;
        }

        $this->seedSyntheticCandles($pairId, $interval, $limit);

        return TradingCandle::where('pair_id', $pairId)
            ->where('interval', $interval)
            ->orderBy('open_time')
            ->limit($limit)
            ->get();
    }

    public function syncHistoricalData(TradingPair $pair, string $interval = '1h', int $limit = 500): void
    {
        try {
            $url = "https://api.binance.com/api/v3/klines?symbol={$pair->symbol}&interval={$interval}&limit={$limit}";
            $res = Http::timeout(8)->get($url);

            if (!$res->successful()) {
                Log::warning('Binance klines failed', ['status' => $res->status()]);
                return;
            }

            foreach ($res->json() as $k) {
                $openTime  = date('Y-m-d H:i:s', (int) ($k[0] / 1000));
                $closeTime = date('Y-m-d H:i:s', (int) ($k[6] / 1000));

                TradingCandle::updateOrCreate(
                    ['pair_id' => $pair->id, 'interval' => $interval, 'open_time' => $openTime],
                    [
                        'close_time' => $closeTime,
                        'open'       => $k[1],
                        'high'       => $k[2],
                        'low'        => $k[3],
                        'close'      => $k[4],
                        'volume'     => $k[5],
                    ]
                );
            }
        } catch (\Throwable $e) {
            Log::warning('ChartService::syncHistoricalData failed', ['msg' => $e->getMessage()]);
        }
    }

    public function seedSyntheticCandles(int $pairId, string $interval = '1h', int $limit = 200): void
    {
        $stepMinutes = match ($interval) {
            '1m'  => 1,
            '5m'  => 5,
            '15m' => 15,
            '1h'  => 60,
            '4h'  => 240,
            '1d'  => 1440,
            default => 60,
        };

        $now  = now();
        $base = 5_000_000.0;
        $rows = [];

        for ($n = 0; $n < $limit; $n++) {
            $openTime  = $now->copy()->subMinutes($stepMinutes * ($limit - 1 - $n));
            $closeTime = $openTime->copy()->addMinutes($stepMinutes);

            $wave  = 50_000 * (($n % 48) / 48.0);
            $open  = round($base + $wave + mt_rand(-3000, 3000), 2);
            $close = round($open + mt_rand(-15000, 15000), 2);
            $high  = round(max($open, $close) + mt_rand(0, 20000), 2);
            $low   = round(min($open, $close) - mt_rand(0, 20000), 2);

            $rows[] = [
                'pair_id'    => $pairId,
                'interval'   => $interval,
                'open_time'  => $openTime->toDateTimeString(),
                'close_time' => $closeTime->toDateTimeString(),
                'open'       => $open,
                'high'       => $high,
                'low'        => $low,
                'close'      => $close,
                'volume'     => round(0.5 + mt_rand(0, 500) / 100.0, 4),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            TradingCandle::insert($chunk);
        }

        Log::info('ChartService: synthetic candles seeded', [
            'pair_id' => $pairId, 'interval' => $interval, 'count' => $limit,
        ]);
    }
}
