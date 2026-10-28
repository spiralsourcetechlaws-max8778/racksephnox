<?php

namespace App\Console\Commands;

use App\Http\Controllers\Admin\DashboardController;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\ViewErrorBag;

class WarmDashboardCache extends Command
{
    protected $signature = 'admin:warm-cache';
    protected $description = 'Render the admin dashboard once so the cache is hot on first user request';

    public function handle(): int
    {
        // Dashboard view may reference auth()->user() — log in a super admin
        $admin = User::where('is_admin', true)
            ->where(function ($q) {
                $q->whereNull('admin_role')->orWhere('admin_role', 'super_admin');
            })
            ->first();

        if (!$admin) {
            $this->warn('No super admin found — skipping dashboard cache warm.');
            return self::SUCCESS;
        }

        auth()->login($admin);
        view()->share('errors', new ViewErrorBag());

        try {
            $ctrl = new DashboardController();
            $result = $ctrl->warmCache();
            $this->info("✅ Dashboard cache warmed: {$result['ms']}ms, {$result['bytes']} bytes");
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('❌ Cache warm failed: ' . $e->getMessage());
            $this->error('   at ' . $e->getFile() . ':' . $e->getLine());
            // Non-fatal — entrypoint wraps in || true
            return self::FAILURE;
        }
    }
}
