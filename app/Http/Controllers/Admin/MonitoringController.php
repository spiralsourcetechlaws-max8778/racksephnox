<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\MonitoringService;

class MonitoringController extends Controller
{
    public function __construct(protected MonitoringService $monitoring) {}

    public function index()
    {
        $checks            = $this->monitoring->check();
        $errors            = $this->monitoring->getRecentErrors(20);
        $pendingMigrations = $checks['migrations']['pending'] ?? 0;
        $overall           = $this->calculateOverall($checks);

        return view('admin.monitoring', compact('checks', 'errors', 'pendingMigrations', 'overall'));
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
