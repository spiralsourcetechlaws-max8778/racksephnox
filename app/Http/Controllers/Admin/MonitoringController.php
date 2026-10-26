<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MonitoringController extends Controller
{
    public function index()
    {
        return view('admin.monitoring', [
            'app'          => $this->appInfo(),
            'system'       => $this->systemInfo(),
            'database'     => $this->databaseInfo(),
            'cache'        => $this->cacheInfo(),
            'queue'        => $this->queueInfo(),
            'storage'      => $this->storageInfo(),
            'logs'         => $this->logInfo(),
            'business'     => $this->businessMetrics(),
            'recentErrors' => $this->recentErrors(),
        ]);
    }

    protected function appInfo(): array
    {
        return [
            'name'           => config('app.name'),
            'environment'    => app()->environment(),
            'debug'          => (bool) config('app.debug'),
            'version'        => app()->version(),
            'php_version'    => PHP_VERSION,
            'timezone'       => config('app.timezone'),
            'url'            => config('app.url'),
            'cache_driver'   => config('cache.default'),
            'queue_driver'   => config('queue.default'),
            'session_driver' => config('session.driver'),
            'db_connection'  => config('database.default'),
        ];
    }

    protected function systemInfo(): array
    {
        $load = function_exists('sys_getloadavg') ? sys_getloadavg() : [0, 0, 0];

        return [
            'os'              => php_uname('s') . ' ' . php_uname('r'),
            'hostname'        => gethostname(),
            'uptime'          => $this->systemUptime(),
            'load_1'          => round($load[0] ?? 0, 2),
            'load_5'          => round($load[1] ?? 0, 2),
            'load_15'         => round($load[2] ?? 0, 2),
            'memory_limit'    => ini_get('memory_limit'),
            'memory_used'     => memory_get_usage(true),
            'memory_peak'     => memory_get_peak_usage(true),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'CLI/Unknown',
            'php_sapi'        => PHP_SAPI,
            'extensions' => [
                'pdo_pgsql'  => extension_loaded('pdo_pgsql'),
                'pdo_sqlite' => extension_loaded('pdo_sqlite'),
                'bcmath'     => extension_loaded('bcmath'),
                'intl'       => extension_loaded('intl'),
                'gd'         => extension_loaded('gd'),
                'zip'        => extension_loaded('zip'),
                'opcache'    => function_exists('opcache_get_status'),
            ],
        ];
    }

    protected function databaseInfo(): array
    {
        $info = [
            'connection'  => config('database.default'),
            'status'      => 'down',
            'ping_ms'     => null,
            'database'    => null,
            'size'        => null,
            'table_count' => null,
            'users'       => null,
            'migrations'  => null,
            'error'       => null,
        ];

        try {
            $start = microtime(true);
            DB::connection()->getPdo();
            $info['status']  = 'up';
            $info['ping_ms'] = round((microtime(true) - $start) * 1000, 2);

            $conn = config('database.default');
            if ($conn === 'pgsql') {
                try {
                    $info['database']    = DB::selectOne('SELECT current_database() as db')->db ?? null;
                    $info['size']        = DB::selectOne("SELECT pg_size_pretty(pg_database_size(current_database())) as size")->size ?? null;
                    $info['table_count'] = DB::selectOne("SELECT count(*) as c FROM information_schema.tables WHERE table_schema = 'public'")->c ?? null;
                } catch (\Throwable $e) {
                    // Ignore — non-critical
                }
            } elseif ($conn === 'sqlite') {
                $info['database'] = config('database.connections.sqlite.database');
                try {
                    $info['table_count'] = DB::select("SELECT name FROM sqlite_master WHERE type='table'")->count();
                } catch (\Throwable $e) {}
            }

            $info['users'] = DB::table('users')->count();

            try {
                Artisan::call('migrate:status');
                $output  = Artisan::output();
                $pending = substr_count($output, 'Pending');
                $info['migrations'] = [
                    'pending' => $pending,
                ];
            } catch (\Throwable $e) {
                $info['migrations'] = ['pending' => null, 'error' => $e->getMessage()];
            }
        } catch (\Throwable $e) {
            $info['error'] = $e->getMessage();
        }

        return $info;
    }

    protected function cacheInfo(): array
    {
        $info = [
            'driver'  => config('cache.default'),
            'status'  => 'unknown',
            'test_ms' => null,
            'error'   => null,
        ];

        try {
            $key   = 'monitor_ping_' . uniqid('', true);
            $start = microtime(true);
            Cache::put($key, 'pong', 10);
            $value = Cache::get($key);
            Cache::forget($key);
            $info['status']  = $value === 'pong' ? 'up' : 'unexpected';
            $info['test_ms'] = round((microtime(true) - $start) * 1000, 2);
        } catch (\Throwable $e) {
            $info['status'] = 'down';
            $info['error']  = $e->getMessage();
        }

        return $info;
    }

    protected function queueInfo(): array
    {
        $info = [
            'driver'         => config('queue.default'),
            'pending'        => null,
            'failed'         => null,
            'oldest_pending' => null,
            'error'          => null,
        ];

        try {
            if (Schema::hasTable('jobs')) {
                $info['pending'] = DB::table('jobs')->count();
                $oldest = DB::table('jobs')->orderBy('created_at')->first();
                $info['oldest_pending'] = $oldest->created_at ?? null;
            }
            if (Schema::hasTable('failed_jobs')) {
                $info['failed'] = DB::table('failed_jobs')->count();
            }
        } catch (\Throwable $e) {
            $info['error'] = $e->getMessage();
        }

        return $info;
    }

    protected function storageInfo(): array
    {
        $info = [
            'free'         => null,
            'total'        => null,
            'used_pct'     => null,
            'log_size'     => null,
            'storage_size' => null,
            'error'        => null,
        ];

        try {
            $root  = base_path();
            $free  = @disk_free_space($root);
            $total = @disk_total_space($root);

            if ($free !== false && $total !== false && $total > 0) {
                $info['free']     = $free;
                $info['total']    = $total;
                $info['used_pct'] = round((($total - $free) / $total) * 100, 1);
            }

            $logFile = storage_path('logs/laravel.log');
            if (file_exists($logFile)) {
                $info['log_size'] = filesize($logFile);
            }

            $info['storage_size'] = $this->dirSize(storage_path());
        } catch (\Throwable $e) {
            $info['error'] = $e->getMessage();
        }

        return $info;
    }

    protected function logInfo(): array
    {
        $info = [
            'log_file'      => storage_path('logs/laravel.log'),
            'exists'        => false,
            'size'          => null,
            'errors_today'  => 0,
            'warnings_today'=> 0,
            'last_modified' => null,
        ];

        try {
            $file = $info['log_file'];
            if (file_exists($file)) {
                $info['exists']        = true;
                $info['size']          = filesize($file);
                $info['last_modified'] = filemtime($file);

                $handle = fopen($file, 'r');
                if ($handle) {
                    $read = min($info['size'], 200 * 1024);
                    if ($read > 0) {
                        fseek($handle, -$read, SEEK_END);
                        $content = fread($handle, $read);
                        fclose($handle);

                        $today = now()->format('Y-m-d');
                        $info['errors_today']   = preg_match_all('/' . preg_quote($today, '/') . '.*\.ERROR:/', $content);
                        $info['warnings_today'] = preg_match_all('/' . preg_quote($today, '/') . '.*\.WARNING:/', $content);
                    } else {
                        fclose($handle);
                    }
                }
            }
        } catch (\Throwable $e) {
            $info['error'] = $e->getMessage();
        }

        return $info;
    }

    protected function recentErrors(): array
    {
        $errors = [];
        $file   = storage_path('logs/laravel.log');

        try {
            if (!file_exists($file)) return [];

            $size   = filesize($file);
            $read   = min($size, 300 * 1024);
            if ($read === 0) return [];

            $handle = fopen($file, 'r');
            if (!$handle) return [];

            fseek($handle, -$read, SEEK_END);
            $content = fread($handle, $read);
            fclose($handle);

            $lines = explode("\n", $content);
            $found = [];
            foreach ($lines as $line) {
                if (str_contains($line, '.ERROR:')) {
                    $found[] = $line;
                }
            }

            $errors = array_slice(array_reverse($found), 0, 10);
        } catch (\Throwable $e) {
            // Silent
        }

        return $errors;
    }

    protected function businessMetrics(): array
    {
        $metrics = [
            'users_total'        => 0,
            'users_today'        => 0,
            'active_sessions'    => 0,
            'transactions_today' => 0,
            'volume_today'       => 0,
            'failed_jobs_24h'    => 0,
        ];

        try {
            $metrics['users_total'] = DB::table('users')->count();
            $metrics['users_today'] = DB::table('users')->whereDate('created_at', today())->count();

            if (Schema::hasTable('sessions')) {
                $metrics['active_sessions'] = DB::table('sessions')
                    ->where('last_activity', '>=', now()->subMinutes(15)->timestamp)
                    ->count();
            }

            if (Schema::hasTable('transactions')) {
                $metrics['transactions_today'] = DB::table('transactions')->whereDate('created_at', today())->count();
                $metrics['volume_today']       = (float) DB::table('transactions')->whereDate('created_at', today())->sum('amount');
            }

            if (Schema::hasTable('failed_jobs')) {
                $metrics['failed_jobs_24h'] = DB::table('failed_jobs')
                    ->where('failed_at', '>=', now()->subDay())
                    ->count();
            }
        } catch (\Throwable $e) {
            // Silent
        }

        return $metrics;
    }

    protected function dirSize(string $path): int
    {
        $size = 0;
        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $size += $file->getSize();
                }
            }
        } catch (\Throwable $e) {
            // Ignore permission errors
        }
        return $size;
    }

    protected function systemUptime(): ?string
    {
        try {
            $uptime = @file_get_contents('/proc/uptime');
            if ($uptime === false) return null;
            $seconds = (int) floatval(explode(' ', $uptime)[0]);
            $days    = (int) floor($seconds / 86400);
            $hours   = (int) floor(($seconds % 86400) / 3600);
            $mins    = (int) floor(($seconds % 3600) / 60);
            return "{$days}d {$hours}h {$mins}m";
        } catch (\Throwable $e) {
            return null;
        }
    }
}
