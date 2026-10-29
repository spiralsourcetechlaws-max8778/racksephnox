<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

class SettingsController extends Controller
{
    public function index()
    {
        if (!Schema::hasTable('settings')) {
            return back()->with('error', 'Settings table does not exist. Run migrations.');
        }

        $defaults = [
            'site_name'              => config('app.name', 'Racksephnox'),
            'support_email'          => 'support@racksephnox.com',
            'deposit_fee_percent'    => '0',
            'withdrawal_fee_percent' => '0',
            'min_withdrawal'         => '100',
            'maintenance_mode'       => '0',
        ];

        foreach ($defaults as $key => $value) {
            if (Setting::where('key', $key)->doesntExist()) {
                Setting::set($key, $value);
            }
        }

        $settings = Setting::pluck('value', 'key')->toArray();
        $stats    = $this->getSystemStats();

        return view('admin.settings.index', compact('settings', 'stats'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'site_name'              => 'required|string|max:255',
            'support_email'          => 'required|email',
            'deposit_fee_percent'    => 'required|numeric|min:0|max:100',
            'withdrawal_fee_percent' => 'required|numeric|min:0|max:100',
            'min_withdrawal'         => 'required|numeric|min:1',
        ]);

        foreach ($validated as $key => $value) {
            Setting::set($key, $value);
        }

        return back()->with('success', 'Settings updated successfully.');
    }

    public function clearCache()
    {
        // Targeted clears — do NOT nuke the app cache (would flush dashboard HTML cache)
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');

        return back()->with('success', 'Config, route, and view caches cleared. Application cache preserved.');
    }

    public function toggleMaintenance(Request $request)
    {
        $currentMode = Setting::get('maintenance_mode', '0');
        $newMode     = $currentMode === '1' ? '0' : '1';
        Setting::set('maintenance_mode', $newMode, 'boolean');

        if ($newMode === '1') {
            Artisan::call('down', ['--secret' => 'racksephnox-admin']);
            return back()->with(
                'success',
                'Maintenance mode ENABLED. Bypass URL: /racksephnox-admin — SAVE THIS. ' .
                'Without it, only a shell (php artisan up) can restore the site.'
            )->with('maintenance_bypass', url('/racksephnox-admin'));
        }

        Artisan::call('up');
        return back()->with('success', 'Maintenance mode DISABLED. Site is live.');
    }

    protected function getSystemStats(): array
    {
        $safe = fn($v) => is_scalar($v) || $v === null ? (string) ($v ?? '') : json_encode($v);

        return [
            'php_version'     => PHP_VERSION,
            'laravel_version' => app()->version(),
            'app_env'         => $safe(config('app.env')),
            'debug_mode'      => config('app.debug') ? 'ON' : 'OFF',
            'cache_driver'    => $safe(config('cache.default')),
            'queue_driver'    => $safe(config('queue.default')),
            'db_driver'       => $safe(config('database.default')),
        ];
    }
}
