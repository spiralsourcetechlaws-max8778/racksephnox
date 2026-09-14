<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TradingSeeder extends Seeder
{
    private function insertSafe(string $table, array $rows): void
    {
        if (!Schema::hasTable($table) || empty($rows)) return;
        $existing = Schema::getColumnListing($table);
        $filtered = array_map(fn ($row) => array_intersect_key($row, array_flip($existing)), $rows);
        foreach (array_chunk($filtered, 200) as $chunk) {
            DB::table($table)->insert($chunk);
        }
    }

    public function run(): void
    {
        $this->command->info('🚀 Seeding trading framework...');

        // Wipe previous run
        foreach (['trading_profiles','trading_accounts','trading_bonus_trackers',
                  'followed_traders','copy_trades','trade_orders',
                  'trading_candles','btc_price_history'] as $t) {
            if (Schema::hasTable($t)) DB::table($t)->delete();
        }

        // Pairs
        if (Schema::hasTable('trading_pairs')) {
            $pairs = [
                ['symbol' => 'BTCUSDT', 'base_currency' => 'BTC', 'quote_currency' => 'USDT',
                 'min_trade_amount' => 0.0001, 'max_trade_amount' => 100, 'tick_size' => 0.0001, 'is_active' => 1],
                ['symbol' => 'ETHUSDT', 'base_currency' => 'ETH', 'quote_currency' => 'USDT',
                 'min_trade_amount' => 0.001,  'max_trade_amount' => 1000, 'tick_size' => 0.001,  'is_active' => 1],
                ['symbol' => 'BNBUSDT', 'base_currency' => 'BNB', 'quote_currency' => 'USDT',
                 'min_trade_amount' => 0.01,   'max_trade_amount' => 5000, 'tick_size' => 0.01,   'is_active' => 1],
            ];
            foreach ($pairs as $p) {
                DB::table('trading_pairs')->updateOrInsert(
                    ['symbol' => $p['symbol']],
                    array_merge($p, ['created_at' => now(), 'updated_at' => now()])
                );
            }
            $this->command->info('✅ Trading pairs: ' . DB::table('trading_pairs')->count());
        }

        $btcPairId = DB::table('trading_pairs')->where('symbol', 'BTCUSDT')->value('id');
        if (!$btcPairId) return;

        // Candles — 6 intervals
        if (Schema::hasTable('trading_candles')) {
            $stepMap = ['1m' => 1, '5m' => 5, '15m' => 15, '1h' => 60, '4h' => 240, '1d' => 1440];
            foreach (['1m' => 500, '5m' => 500, '15m' => 500, '1h' => 500, '4h' => 500, '1d' => 365] as $interval => $limit) {
                DB::table('trading_candles')->where('pair_id', $btcPairId)->where('interval', $interval)->delete();
                $rows = [];
                $now = now();
                $step = $stepMap[$interval];
                for ($n = 0; $n < $limit; $n++) {
                    $openTime  = $now->copy()->subMinutes($step * ($limit - 1 - $n));
                    $closeTime = $openTime->copy()->addMinutes($step);
                    $wave = 50_000 * (($n % 48) / 48.0);
                    $open  = round(5_000_000 + $wave + mt_rand(-3000, 3000), 2);
                    $close = round($open + mt_rand(-15000, 15000), 2);
                    $high  = round(max($open, $close) + mt_rand(0, 20000), 2);
                    $low   = round(min($open, $close) - mt_rand(0, 20000), 2);
                    $rows[] = [
                        'pair_id'    => $btcPairId, 'interval' => $interval,
                        'open_time'  => $openTime->toDateTimeString(),
                        'close_time' => $closeTime->toDateTimeString(),
                        'open' => $open, 'high' => $high, 'low' => $low, 'close' => $close,
                        'volume' => round(0.5 + mt_rand(0, 500) / 100.0, 4),
                        'created_at' => now(), 'updated_at' => now(),
                    ];
                }
                $this->insertSafe('trading_candles', $rows);
                $this->command->info("   ✅ {$interval}: {$limit} candles");
            }
        }

        // BTC price history
        if (Schema::hasTable('btc_price_history')) {
            $rows = [];
            $now = now();
            for ($n = 0; $n < 720; $n++) {
                $ts = $now->copy()->subHours(719 - $n);
                $wave = 200_000 * sin($n / 24.0);
                $priceKes = round(5_000_000 + $wave + mt_rand(-50_000, 50_000), 2);
                $rows[] = [
                    'price' => round($priceKes / 130, 2),
                    'price_kes' => $priceKes,
                    'high' => round($priceKes * 1.015, 2),
                    'low' => round($priceKes * 0.985, 2),
                    'volume' => round(0.5 + mt_rand(0, 500) / 100.0, 4),
                    'percent_change_24h' => round(mt_rand(-400, 400) / 100.0, 2),
                    'recorded_at' => $ts->toDateTimeString(),
                    'created_at' => now(), 'updated_at' => now(),
                ];
            }
            $this->insertSafe('btc_price_history', $rows);
            $this->command->info('✅ BTC price history: ' . DB::table('btc_price_history')->count());
        }

        // Accounts, profiles, bonus trackers
        $userIds = DB::table('users')->pluck('id');

        if (Schema::hasTable('trading_accounts')) {
            foreach ($userIds as $uid) {
                DB::table('trading_accounts')->updateOrInsert(
                    ['user_id' => $uid],
                    [
                        'balance'        => round(mt_rand(500_000, 5_000_000) / 100, 2),
                        'locked_balance' => 0,
                        'btc_balance'    => round(mt_rand(0, 100000) / 100000, 8),
                        'created_at'     => now(), 'updated_at' => now(),
                    ]
                );
            }
            $this->command->info('✅ Trading accounts: ' . DB::table('trading_accounts')->count());
        }

        if (Schema::hasTable('trading_profiles')) {
            foreach ($userIds as $uid) {
                $totalTrades = mt_rand(1, 200);
                $wins = mt_rand(0, $totalTrades);
                DB::table('trading_profiles')->updateOrInsert(
                    ['user_id' => $uid],
                    [
                        'username'           => 'trader_' . $uid,
                        'display_name'       => 'Trader ' . $uid,
                        'is_public'          => 1,
                        'allow_copy_trading' => 1,
                        'copy_ratio'         => 1.0,
                        'total_trades'       => $totalTrades,
                        'winning_trades'     => $wins,
                        'total_profit'       => round(mt_rand(-50000, 250000) / 10, 2),
                        'created_at'         => now(), 'updated_at' => now(),
                    ]
                );
            }
            $this->command->info('✅ Trading profiles: ' . DB::table('trading_profiles')->count());
        }

        if (Schema::hasTable('trading_bonus_trackers')) {
            foreach ($userIds as $uid) {
                DB::table('trading_bonus_trackers')->updateOrInsert(
                    ['user_id' => $uid],
                    [
                        'bonus_type'      => 'signup',
                        'bonus_amount'    => 100.00,
                        'required_volume' => 1000.00,
                        'achieved_volume' => round(mt_rand(0, 800) / 10, 2),
                        'is_claimed'      => 0,
                        'expires_at'      => now()->addDays(30),
                        'created_at'      => now(), 'updated_at' => now(),
                    ]
                );
            }
            $this->command->info('✅ Bonus trackers: ' . DB::table('trading_bonus_trackers')->count());
        }

        // Sample orders
        if (Schema::hasTable('trade_orders') && $userIds->isNotEmpty()) {
            $rows = [];
            $statuses = ['pending', 'partial', 'completed', 'cancelled'];
            for ($i = 0; $i < 30; $i++) {
                $side = $i % 2 === 0 ? 'buy' : 'sell';
                $status = $statuses[array_rand($statuses)];
                $amountBtc = round(mt_rand(1, 500) / 10000, 8);
                $limitPrice = round(mt_rand(4_800_000, 5_200_000) / 100, 2);
                $filled = $status === 'completed' ? $amountBtc
                        : ($status === 'partial'   ? round($amountBtc * 0.5, 8) : 0);
                $rows[] = [
                    'user_id'       => $userIds->random(),
                    'pair_id'       => $btcPairId,
                    'side'          => $side,
                    'order_type'    => ['market','limit','stop'][array_rand([0,1,2])],
                    'amount_btc'    => $amountBtc,
                    'filled_amount' => $filled,
                    'limit_price'   => $limitPrice,
                    'price_per_btc' => $limitPrice,
                    'filled_kes'    => round($filled * $limitPrice, 2),
                    'status'        => $status,
                    'time_in_force' => 'GTC',
                    'created_at'    => now()->subDays(mt_rand(0, 20))->subHours(mt_rand(0, 23)),
                    'updated_at'    => now(),
                ];
            }
            $this->insertSafe('trade_orders', $rows);
            $this->command->info('✅ Trade orders: ' . DB::table('trade_orders')->count());
        }

        // Followed traders
        if (Schema::hasTable('followed_traders') && $userIds->count() >= 2) {
            $this->insertSafe('followed_traders', [[
                'follower_id'     => $userIds[0],
                'trader_id'       => $userIds[1],
                'copy_ratio'      => 0.5,
                'auto_copy'       => 1,
                'max_copy_amount' => 10_000,
                'created_at'      => now(), 'updated_at' => now(),
            ]]);
            $this->command->info('✅ Followed traders: ' . DB::table('followed_traders')->count());
        }

        $this->command->info('');
        $this->command->info('═══════════════════════════════════════════');
        $this->command->info('✅ Trading framework seeded.');
        $this->command->info('═══════════════════════════════════════════');
    }
}
