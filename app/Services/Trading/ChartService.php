<?php

namespace App\Services\Trading;

use App\Models\TradingCandle;
use App\Models\TradingPair;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChartService
{
    protected array $intervals = ['1m', '5m', '15m', '1h', '4h', '1d'];

    protected array $intervalSeconds = [
        '1m'  => 60,
        '5m'  => 300,
        '15m' => 900,
        '1h'  => 3600,
        '4h'  => 14400,
        '1d'  => 86400,
    ];

    /**
     * Return candles for a pair+interval. Tries cache → DB → aggregation → Binance → synthetic.
     */
    public function getCandles(int $pairId, string $interval = '1h', int $limit = 500): Collection
    {
        if (!isset($this->intervalSeconds[$interval])) {
            $interval = '1h';
        }

        $cacheKey = "candles:{$pairId}:{$interval}:{$limit}";

        return Cache::remember($cacheKey, 30, function () use ($pairId, $interval, $limit) {
            $pair = TradingPair::find($pairId);
            if (!$pair) return collect();

            // 1. Try DB (fresh enough?)
            $candles = $this->freshCandles($pairId, $interval, $limit);
            if ($candles->count() >= 10) {
                return $this->fillGaps($candles, $interval);
            }

            // 2. Try Binance API
            $this->syncHistoricalData($pair, $interval, $limit);
            $candles = $this->freshCandles($pairId, $interval, $limit);
            if ($candles->count() >= 10) {
                return $this->fillGaps($candles, $interval);
            }

            // 3. Try aggregation from a smaller interval
            $aggregated = $this->aggregateFromSmaller($pairId, $interval, $limit);
            if ($aggregated->count() >= 10) {
                return $aggregated;
            }

            // 4. Synthetic fallback
            $this->seedSyntheticCandles($pairId, $interval, $limit);
            $candles = $this->freshCandles($pairId, $interval, $limit);

            return $this->fillGaps($candles, $interval);
        });
    }

    public function invalidateCache(int $pairId, ?string $interval = null): void
    {
        if ($interval) {
            Cache::forget("candles:{$pairId}:{$interval}:500");
        } else {
            foreach ($this->intervals as $iv) {
                Cache::forget("candles:{$pairId}:{$iv}:500");
            }
        }
    }

    protected function freshCandles(int $pairId, string $interval, int $limit): Collection
    {
        return TradingCandle::where('pair_id', $pairId)
            ->where('interval', $interval)
            ->orderBy('open_time', 'desc')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();
    }

    /**
     * Fill gaps between consecutive candles with flat synthetic candles
     * (using previous close as O/H/L/C with 0 volume). Result: continuous chart.
     */
    protected function fillGaps(Collection $candles, string $interval): Collection
    {
        if ($candles->isEmpty()) return $candles;

        $step = $this->intervalSeconds[$interval] ?? 3600;
        $filled = collect();

        foreach ($candles as $c) {
            $ts = $c->open_time instanceof Carbon
                ? $c->open_time->timestamp
                : strtotime((string) $c->open_time);

            if ($filled->isNotEmpty()) {
                $prevTs = $filled->last()['time'];
                $expected = $prevTs + $step;
                $prevClose = $filled->last()['close'];

                // Insert flat candles for each missing slot
                while ($expected < $ts) {
                    $filled->push([
                        'time'   => $expected,
                        'open'   => (float) $prevClose,
                        'high'   => (float) $prevClose,
                        'low'    => (float) $prevClose,
                        'close'  => (float) $prevClose,
                        'volume' => 0,
                    ]);
                    $expected += $step;
                }
            }

            $filled->push([
                'time'   => (int) $ts,
                'open'   => (float) $c->open,
                'high'   => (float) $c->high,
                'low'    => (float) $c->low,
                'close'  => (float) $c->close,
                'volume' => (float) ($c->volume ?? 0),
            ]);
        }

        return $filled;
    }

    /**
     * If a specific interval has no data, aggregate from a smaller one.
     */
    protected function aggregateFromSmaller(int $pairId, string $targetInterval, int $limit): Collection
    {
        $order = array_flip(array_keys($this->intervalSeconds));
        $targetIdx = $order[$targetInterval] ?? null;
        if ($targetIdx === null || $targetIdx === 0) return collect();

        // Pick next smaller interval that has data
        for ($i = $targetIdx - 1; $i >= 0; $i--) {
            $baseInterval = array_keys($this->intervalSeconds)[$i];
            $baseCandles = TradingCandle::where('pair_id', $pairId)
                ->where('interval', $baseInterval)
                ->orderBy('open_time', 'desc')
                ->limit($limit * 60)
                ->get()
                ->reverse()
                ->values();

            if ($baseCandles->count() < 10) continue;

            $bucketSeconds = $this->intervalSeconds[$targetInterval];
            $buckets = [];

            foreach ($baseCandles as $c) {
                $ts = $c->open_time instanceof Carbon ? $c->open_time->timestamp : strtotime((string) $c->open_time);
                $bucketTs = (int) floor($ts / $bucketSeconds) * $bucketSeconds;

                if (!isset($buckets[$bucketTs])) {
                    $buckets[$bucketTs] = [
                        'time'   => $bucketTs,
                        'open'   => (float) $c->open,
                        'high'   => (float) $c->high,
                        'low'    => (float) $c->low,
                        'close'  => (float) $c->close,
                        'volume' => (float) ($c->volume ?? 0),
                    ];
                } else {
                    $buckets[$bucketTs]['high']   = max($buckets[$bucketTs]['high'], (float) $c->high);
                    $buckets[$bucketTs]['low']    = min($buckets[$bucketTs]['low'], (float) $c->low);
                    $buckets[$bucketTs]['close']  = (float) $c->close;
                    $buckets[$bucketTs]['volume'] += (float) ($c->volume ?? 0);
                }
            }

            ksort($buckets);
            return collect(array_slice(array_values($buckets), -$limit));
        }

        return collect();
    }

    public function syncHistoricalData(TradingPair $pair, string $interval = '1h', int $limit = 500): void
    {
        try {
            $url = "https://api.binance.com/api/v3/klines?symbol={$pair->symbol}&interval={$interval}&limit={$limit}";
            $res = Http::timeout(8)->get($url);

            if (!$res->successful()) {
                Log::warning('Binance klines failed', ['status' => $res->status(), 'symbol' => $pair->symbol]);
                return;
            }

            $rows = [];
            foreach ($res->json() as $k) {
                $rows[] = [
                    'pair_id'    => $pair->id,
                    'interval'   => $interval,
                    'open_time'  => date('Y-m-d H:i:s', (int) ($k[0] / 1000)),
                    'close_time' => date('Y-m-d H:i:s', (int) ($k[6] / 1000)),
                    'open'       => (float) $k[1],
                    'high'       => (float) $k[2],
                    'low'        => (float) $k[3],
                    'close'      => (float) $k[4],
                    'volume'     => (float) $k[5],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if (!empty($rows)) {
                foreach (array_chunk($rows, 200) as $chunk) {
                    TradingCandle::upsert(
                        $chunk,
                        ['pair_id', 'interval', 'open_time'],
                        ['open', 'high', 'low', 'close', 'volume', 'close_time', 'updated_at']
                    );
                }
                $this->invalidateCache($pair->id, $interval);
            }
        } catch (\Throwable $e) {
            Log::error('ChartService sync failed', [
                'symbol'   => $pair->symbol,
                'interval' => $interval,
                'error'    => $e->getMessage(),
            ]);
        }
    }

    protected function seedSyntheticCandles(int $pairId, string $interval, int $limit): void
    {
        $step = $this->intervalSeconds[$interval] ?? 3600;
        $existing = TradingCandle::where('pair_id', $pairId)
            ->where('interval', $interval)
            ->orderBy('open_time', 'desc')
            ->limit(1)
            ->first();

        $lastClose = $existing ? (float) $existing->close : 5000000;
        $startTs = $existing
            ? Carbon::parse($existing->open_time)->timestamp + $step
            : now()->subSeconds($step * $limit)->timestamp;

        $rows = [];
        for ($i = 0; $i < $limit; $i++) {
            $openTs = $startTs + ($i * $step);
            $open = $lastClose;
            $variance = $open * (mt_rand(-50, 50) / 10000);
            $close = round($open + $variance, 2);
            $high = round(max($open, $close) + abs($open * mt_rand(0, 30) / 10000), 2);
            $low  = round(min($open, $close) - abs($open * mt_rand(0, 30) / 10000), 2);

            $rows[] = [
                'pair_id'    => $pairId,
                'interval'   => $interval,
                'open_time'  => date('Y-m-d H:i:s', $openTs),
                'close_time' => date('Y-m-d H:i:s', $openTs + $step),
                'open'       => $open,
                'high'       => $high,
                'low'        => $low,
                'close'      => $close,
                'volume'     => round(mt_rand(10, 500) / 100, 4),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $lastClose = $close;
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            TradingCandle::insert($chunk);
        }

        Log::info('ChartService: synthetic candles seeded', ['pair_id' => $pairId, 'interval' => $interval, 'count' => $limit]);
    }
}
