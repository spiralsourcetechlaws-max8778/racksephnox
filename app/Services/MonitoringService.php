<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MonitoringService
{
    public function check(): array
    {
        return [
            'database'  => $this->checkDatabase(),
            'cache'     => $this->checkCache(),
            'queue'     => $this->checkQueue(),
            'storage'   => $this->checkStorage(),
            'config'    => $this->checkConfig(),
            'scheduler' => $this->checkScheduler(),
            'migrations'=> $this->checkMigrations(),
        ];
    }

    protected function checkDatabase(): array
    {
        try {
            $start = microtime(true);
            DB::connection()->getPdo();
            $ms = (int) round((microtime(true) - $start) * 1000);

            return [
                'status'      => $ms > 500 ? 'warning' : 'ok',
                'driver'      => DB::connection()->getDriverName(),
                'latency_ms'  => $ms,
                'users_count' => Schema::hasTable('users') ? DB::table('users')->count() : 0,
                'migrations'  => Schema::hasTable('migrations') ? DB::table('migrations')->count() : 0,
            ];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    protected function checkCache(): array
    {
        try {
            $key = 'monitor_probe_' . uniqid('', true);
            $start = microtime(true);
            Cache::put($key, 'ok', 5);
            $ok = Cache::get($key) === 'ok';
            Cache::forget($key);

            return [
                'status'     => $ok ? 'ok' : 'error',
                'driver'     => (string) config('cache.default'),
                'latency_ms' => (int) round((microtime(true) - $start) * 1000),
            ];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    protected function checkQueue(): array
    {
        try {
            $pending = 0;
            $failed  = 0;

            if (Schema::hasTable('jobs')) {
                $pending = DB::table('jobs')->count();
            }
            if (Schema::hasTable('failed_jobs')) {
                $failed = DB::table('failed_jobs')->count();
            }

            return [
                'status'  => $failed > 50 ? 'warning' : 'ok',
                'driver'  => (string) config('queue.default'),
                'pending' => $pending,
                'failed'  => $failed,
            ];
        } catch (\Throwable $e) {
            return ['status' => 'warning', 'message' => 'Queue status unavailable'];
        }
    }

    protected function checkStorage(): array
    {
        try {
            $path = storage_path();
            $free = function_exists('disk_free_space') ? @disk_free_space($path) : null;

            return [
                'status'   => is_writable($path) ? 'ok' : 'error',
                'writable' => is_writable($path),
                'free_gb'  => $free ? round($free / 1073741824, 2) : null,
            ];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    protected function checkConfig(): array
    {
        return [
            'status'        => config('app.debug') ? 'warning' : 'ok',
            'app_env'       => (string) config('app.env'),
            'debug'         => (bool) config('app.debug'),
            'app_url'       => (string) config('app.url'),
            'timezone'      => (string) config('app.timezone'),
            'config_cached' => file_exists(base_path('bootstrap/cache/config.php')),
            'routes_cached' => file_exists(base_path('bootstrap/cache/routes-v7.php')),
        ];
    }

    protected function checkScheduler(): array
    {
        try {
            $lastRun = Cache::get('scheduler.heartbeat');
            $healthy = $lastRun && now()->diffInMinutes(\Carbon\Carbon::parse($lastRun)) < 10;

            return [
                'status'   => $healthy ? 'ok' : 'warning',
                'last_run' => $lastRun,
                'healthy'  => $healthy,
            ];
        } catch (\Throwable $e) {
            return ['status' => 'warning', 'message' => $e->getMessage()];
        }
    }

    protected function checkMigrations(): array
    {
        try {
            // Cache for 5 minutes to prevent migrate:status from slowing down every page load
            return Cache::remember('monitoring.pending_migrations', 300, function () {
                Artisan::call('migrate:status');
                $output = Artisan::output();
                $pending = substr_count($output, 'Pending');
                return [
                    'status'  => $pending > 0 ? 'warning' : 'ok',
                    'pending' => $pending,
                ];
            });
        } catch (\Throwable $e) {
            return ['status' => 'warning', 'pending' => 0];
        }
    }

    public function getRecentErrors(int $limit = 20): array
    {
        try {
            $logFile = storage_path('logs/laravel.log');
            if (!file_exists($logFile) || !is_readable($logFile)) {
                return [];
            }

            // Read only the last 1MB to avoid memory exhaustion on large log files
            $handle = fopen($logFile, 'r');
            if (!$handle) return [];

            $content = '';
            fseek($handle, -1, SEEK_END);
            $size = ftell($handle);
            $readFrom = max(0, $size - 1024 * 1024);
            fseek($handle, $readFrom);
            $content = fread($handle, 1024 * 1024);
            fclose($handle);

            $lines = explode("\n", $content);
            $errors = [];
            foreach ($lines as $line) {
                if (stripos($line, '.ERROR') !== false || stripos($line, 'production.ERROR') !== false) {
                    $errors[] = trim(substr($line, 0, 300));
                }
            }
            return array_slice(array_reverse($errors), 0, $limit);
        } catch (\Throwable $e) {
            return [];
        }
    }
}
