<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
        ];
    }

    protected function checkDatabase(): array
    {
        try {
            $start = microtime(true);
            DB::connection()->getPdo();
            $ms = (int) round((microtime(true) - $start) * 1000);

            $users = DB::table('users')->count();
            $migrations = DB::table('migrations')->count();

            return [
                'status'      => $ms > 500 ? 'warning' : 'ok',
                'driver'      => DB::connection()->getDriverName(),
                'latency_ms'  => $ms,
                'users_count' => $users,
                'migrations'  => $migrations,
            ];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    protected function checkCache(): array
    {
        try {
            $key = 'monitor_probe_' . uniqid();
            $start = microtime(true);
            Cache::put($key, 'ok', 5);
            $ok = Cache::get($key) === 'ok';
            Cache::forget($key);
            $ms = (int) round((microtime(true) - $start) * 1000);

            return [
                'status' => $ok ? 'ok' : 'error',
                'driver' => config('cache.default'),
                'latency_ms' => $ms,
            ];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    protected function checkQueue(): array
    {
        try {
            $pending = 0;
            $failed = 0;
            if (\Schema::hasTable('jobs')) {
                $pending = DB::table('jobs')->count();
            }
            if (\Schema::hasTable('failed_jobs')) {
                $failed = DB::table('failed_jobs')->count();
            }

            return [
                'status'   => $failed > 50 ? 'warning' : 'ok',
                'driver'   => config('queue.default'),
                'pending'  => $pending,
                'failed'   => $failed,
            ];
        } catch (\Throwable $e) {
            return ['status' => 'warning', 'message' => 'Queue status unavailable'];
        }
    }

    protected function checkStorage(): array
    {
        try {
            $path = storage_path();
            $writable = is_writable($path);
            $free = function_exists('disk_free_space') ? disk_free_space($path) : null;

            return [
                'status'      => $writable ? 'ok' : 'error',
                'writable'    => $writable,
                'free_gb'     => $free ? round($free / 1073741824, 2) : null,
            ];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    protected function checkConfig(): array
    {
        return [
            'status'          => config('app.debug') ? 'warning' : 'ok',
            'app_env'         => config('app.env'),
            'debug'           => config('app.debug'),
            'app_url'         => config('app.url'),
            'timezone'        => config('app.timezone'),
            'config_cached'   => file_exists(base_path('bootstrap/cache/config.php')),
            'routes_cached'   => file_exists(base_path('bootstrap/cache/routes-v7.php')),
        ];
    }

    protected function checkScheduler(): array
    {
        try {
            $lastRun = Cache::get('scheduler.heartbeat');
            $healthy = $lastRun && now()->diffInMinutes($lastRun) < 10;

            return [
                'status'    => $healthy ? 'ok' : 'warning',
                'last_run'  => $lastRun,
                'healthy'   => $healthy,
            ];
        } catch (\Throwable $e) {
            return ['status' => 'warning', 'message' => $e->getMessage()];
        }
    }

    public function getRecentErrors(int $limit = 20): array
    {
        try {
            $logFile = storage_path('logs/laravel.log');
            if (!file_exists($logFile)) return [];

            $lines = array_slice(file($logFile), -500);
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
