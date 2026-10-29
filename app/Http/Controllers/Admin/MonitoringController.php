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
        $logErrors         = $this->monitoring->getRecentErrors(20);   // ← renamed
        $pendingMigrations = $checks['migrations']['pending'] ?? 0;
        $overall           = $this->calculateOverall($checks);

        // Important: do NOT name this $errors — that key is reserved for the
        // ViewErrorBag that Laravel's ShareErrorsFromSession middleware injects
        // into every view. Passing $errors as an array here breaks any layout
        // that calls $errors->any() (which every admin/user layout does).
        return view('admin.monitoring', compact('checks', 'logErrors', 'pendingMigrations', 'overall'));
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
