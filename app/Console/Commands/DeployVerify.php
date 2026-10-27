<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Post-deploy sanity check. Runs at the tail of docker/entrypoint.sh so
 * every deploy prints an auditable state block into the Render log.
 *
 * Read-only. Never writes. Never throws unless the DB itself is down.
 * If the DB is down, entrypoint.sh wraps the call in `|| true` so the
 * deploy still proceeds to supervisord.
 */
class DeployVerify extends Command
{
    protected $signature = 'deploy:verify';
    protected $description = 'Print post-deploy audit: seed_runs, candle counts, schema drift, row counts';

    private array $warnings = [];

    public function handle(): int
    {
        $this->info('══════════════════════════════════════════════════════════════');
        $this->info('  POST-DEPLOY VERIFICATION  ·  ' . now()->toDateTimeString());
        $this->info('  env: ' . app()->environment() . '  ·  commit: ' . substr(env('RENDER_GIT_COMMIT', env('GIT_COMMIT', 'unknown')), 0, 7));
        $this->info('══════════════════════════════════════════════════════════════');

        $this->checkSeedRuns();
        $this->checkCandles();
        $this->checkBtcPrices();
        $this->checkSchemaDrift();
        $this->checkRowCounts();

        $this->info('');
        if (empty($this->warnings)) {
            $this->info('✅  NO WARNINGS — deploy healthy');
        } else {
            $this->warn('⚠️   ' . count($this->warnings) . ' warning(s):');
            foreach ($this->warnings as $w) {
                $this->warn('     • ' . $w);
            }
        }
        $this->info('══════════════════════════════════════════════════════════════');

        return self::SUCCESS;
    }

    /* ─── 1. seed_runs ─────────────────────────────────────────── */

    private function checkSeedRuns(): void
    {
        $this->info('');
        $this->info('── seed_runs (last 30 minutes) ───────────────────────────────');

        if (!Schema::hasTable('seed_runs')) {
            $this->warn('   ⚠️  table missing — migration 2026_09_13_000010 never ran');
            $this->warnings[] = 'seed_runs table missing';
            return;
        }

        $rows = DB::table('seed_runs')
            ->select('seeder', 'status', 'duration_ms', 'error', 'run_at')
            ->where('run_at', '>', now()->subMinutes(30))
            ->orderBy('run_at', 'asc')
            ->get();

        if ($rows->isEmpty()) {
            $this->warn('   ⚠️  no seed_runs rows in the last 30 min');
            $this->warn('       — seeder trait not wired OR db:seed was skipped');
            $this->warnings[] = 'no seed_runs rows in last 30 min';
            return;
        }

        $failed = 0;
        foreach ($rows as $r) {
            $name   = class_basename($r->seeder);
            $ms     = $r->duration_ms ?? '?';
            if ($r->status === 'ok') {
                $this->line(sprintf('   ✅ %-42s %sms', $name, $ms));
            } else {
                $this->error(sprintf('   ❌ %-42s %sms   %s',
                    $name, $ms, substr((string) $r->error, 0, 80)));
                $failed++;
            }
        }

        $this->line(sprintf('   total: %d   failed: %d', $rows->count(), $failed));
        if ($failed > 0) {
            $this->warnings[] = "{$failed} seeder(s) failed";
        }
    }

    /* ─── 2. trading_candles ───────────────────────────────────── */

    private function checkCandles(): void
    {
        $this->info('');
        $this->info('── trading_candles per interval ──────────────────────────────');

        if (!Schema::hasTable('trading_candles')) {
            $this->warn('   ⚠️  table missing');
            $this->warnings[] = 'trading_candles table missing';
            return;
        }

        $hasCloseTime = Schema::hasColumn('trading_candles', 'close_time');
        $this->line('   close_time column : ' . ($hasCloseTime ? '✅ present' : '❌ MISSING'));
        if (!$hasCloseTime) {
            $this->warnings[] = 'trading_candles.close_time missing';
        }

        $counts = DB::table('trading_candles')
            ->select('interval', DB::raw('count(*) as n'))
            ->groupBy('interval')
            ->orderBy('interval')
            ->get();

        if ($counts->isEmpty()) {
            $this->warn('   ⚠️  0 candles total — seeder did not insert anything');
            $this->warnings[] = 'trading_candles empty';
            return;
        }

        $total = 0;
        foreach ($counts as $c) {
            $total += $c->n;
            $icon = $c->n > 0 ? '✅' : '⚠️ ';
            $this->line(sprintf('   %s %-4s %d', $icon, $c->interval, $c->n));
        }
        $this->line("   total: {$total}");
    }

    /* ─── 3. btc_price_histories ───────────────────────────────── */

    private function checkBtcPrices(): void
    {
        $this->info('');
        $this->info('── btc_price_histories ───────────────────────────────────────');

        if (!Schema::hasTable('btc_price_histories')) {
            $this->warn('   ⚠️  table missing');
            $this->warnings[] = 'btc_price_histories table missing';
            return;
        }

        $count  = DB::table('btc_price_histories')->count();
        $latest = DB::table('btc_price_histories')->orderByDesc('recorded_at')->value('recorded_at');
        $hasCurrency = Schema::hasColumn('btc_price_histories', 'currency');

        $this->line("   rows               : {$count}");
        $this->line("   latest recorded_at : " . ($latest ?: '—'));
        $this->line("   currency column    : " . ($hasCurrency ? '✅' : '❌'));

        if ($count === 0) {
            $this->warnings[] = 'btc_price_histories empty';
        }
        if (!$hasCurrency) {
            $this->warnings[] = 'btc_price_histories.currency missing';
        }
    }

    /* ─── 4. schema drift ──────────────────────────────────────── */

    private function checkSchemaDrift(): void
    {
        $this->info('');
        $this->info('── schema drift check (columns the code needs) ───────────────');

        $expect = [
            'trading_candles'     => ['close_time', 'open_time', 'open', 'close', 'high', 'low', 'volume', 'interval', 'pair_id'],
            'trade_orders'        => ['amount_btc', 'filled_amount', 'filled_kes', 'limit_price', 'status', 'side', 'user_id', 'pair_id'],
            'trading_accounts'    => ['balance', 'locked_balance', 'btc_balance', 'total_pnl', 'currency'],
            'btc_price_histories' => ['price', 'price_kes', 'currency', 'recorded_at'],
            'investment_plans'    => ['name', 'min_amount', 'max_amount', 'daily_interest_rate', 'duration_days'],
            'machines'            => ['code', 'name', 'growth_rate', 'duration_days'],
            'lottery_symbols'     => ['name', 'display_name', 'icon'],
        ];

        foreach ($expect as $table => $cols) {
            if (!Schema::hasTable($table)) {
                $this->error("   ❌ {$table}: TABLE MISSING");
                $this->warnings[] = "{$table} table missing";
                continue;
            }
            $have    = Schema::getColumnListing($table);
            $missing = array_values(array_diff($cols, $have));
            if (!empty($missing)) {
                $this->error("   ❌ {$table}: missing " . implode(', ', $missing));
                $this->warnings[] = "{$table} missing: " . implode(',', $missing);
            } else {
                $this->line("   ✅ {$table}");
            }
        }
    }

    /* ─── 5. row counts ────────────────────────────────────────── */

    private function checkRowCounts(): void
    {
        $this->info('');
        $this->info('── row counts ────────────────────────────────────────────────');

        $tables = [
            'users', 'wallets',
            'investment_plans', 'machines', 'machine_vips',
            'loan_products',
            'lottery_symbols', 'lottery_games',
            'trading_pairs', 'trading_candles', 'btc_price_histories',
            'trade_orders', 'trading_accounts',
            'transactions',
        ];

        foreach ($tables as $t) {
            if (!Schema::hasTable($t)) {
                $this->line(sprintf('   %-24s [missing]', $t));
                continue;
            }
            $n = DB::table($t)->count();
            $icon = $n > 0 ? '  ' : '⚠️ ';
            $this->line(sprintf('   %s %-24s %d', $icon, $t, $n));

            if ($n === 0 && in_array($t, ['investment_plans', 'lottery_symbols', 'trading_pairs'], true)) {
                $this->warnings[] = "{$t} is empty";
            }
        }
    }
}
