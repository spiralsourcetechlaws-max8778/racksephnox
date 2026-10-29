<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\MonitoringService;
use Illuminate\Support\Facades\Artisan;

class MonitoringController extends Controller
{
    public function __construct(protected MonitoringService $monitoring) {}

    public function index()
    {
        $checks = $this->monitoring->check();
        $errors = $this->monitoring->getRecentErrors(20);
        $pendingMigrations = $this->countPendingMigrations();
        $overall = $this->calculateOverall($checks);

        return view('admin.monitoring', compact('checks', 'errors', 'pendingMigrations', 'overall'));
    }

    protected function countPendingMigrations(): int
    {
        try {
            Artisan::call('migrate:status');
            return substr_count(Artisan::output(), 'Pending');
        } catch (\Throwable $e) {
            return 0;
        }
    }

    protected function calculateOverall(array $checks): string
    {
        foreach ($checks as $check) {
            if (($check['status'] ?? 'ok') === 'error') return 'error';
        }
        foreach ($checks as $check) {
            if (($check['status'] ?? 'ok') === 'warning') return 'warning';
        }
        return 'ok';
    }
}
