<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class SettingsController extends Controller
{
    public function index()
    {
        // Ensure defaults exist
        $defaults = [
            'site_name' => 'Racksephnox',
            'support_email' => 'support@racksephnox.com',
            'deposit_fee_percent' => '0',
            'withdrawal_fee_percent' => '0',
            'min_withdrawal' => '100',
            'maintenance_mode' => '0',
        ];

        foreach ($defaults as $key => $value) {
            if (Setting::where('key', $key)->doesntExist()) {
                Setting::set($key, $value);
            }
        }

        $settings = Setting::pluck('value', 'key')->toArray();
        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'site_name' => 'required|string|max:255',
            'support_email' => 'required|email',
            'deposit_fee_percent' => 'required|numeric|min:0|max:100',
            'withdrawal_fee_percent' => 'required|numeric|min:0|max:100',
            'min_withdrawal' => 'required|numeric|min:1',
        ]);

        foreach ($validated as $key => $value) {
            Setting::set($key, $value);
        }

        return back()->with('success', 'Settings updated successfully.');
    }

    /**
     * Clear CONFIG / ROUTE / VIEW caches — NOT the app cache.
     *
     * The previous implementation used Artisan::call('optimize:clear'),
     * which also flushes the application cache. That included the
     * admin dashboard HTML cache, forcing a 20-60s cold rebuild on
     * the next visitor. Now only the caches that matter for UI
     * changes are cleared; the app cache (dashboard, stats) is
     * preserved.
     *
     * If you DO want to fully flush the app cache, use the artisan
     * command via a shell (php artisan cache:clear) — but that
     * means accepting a cold dashboard on the next request.
     */
    public function clearCache()
    {
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');

        return back()->with('success', 'Config, route, and view caches cleared. (Application cache preserved — the admin dashboard stays warm.)');
    }

    /**
     * Toggle maintenance mode.
     *
     * Careful UX: enabling puts the whole app into 503 (including
     * this page). The bypass URL is shown ONCE in the success flash —
     * if it's missed, only `php artisan up` from a shell restores the
     * app. On Render free tier there is no shell, so this is serious.
     *
     * The blade view wraps this button in a confirm() dialog to
     * prevent accidental clicks.
     */
    public function toggleMaintenance(Request $request)
    {
        $currentMode = Setting::get('maintenance_mode', '0');
        $newMode = $currentMode === '1' ? '0' : '1';

        Setting::set('maintenance_mode', $newMode, 'boolean');

        if ($newMode === '1') {
            Artisan::call('down', ['--secret' => 'racksephnox-admin']);

            return back()->with(
                'success',
                'Maintenance mode ENABLED. Bypass URL: /racksephnox-admin — SAVE THIS. ' .
                'Without it, only a shell (php artisan up) can restore the site. ' .
                'On Render free tier there is no shell — do not lose this URL.'
            )->with('maintenance_bypass', url('/racksephnox-admin'));
        }

        Artisan::call('up');
        return back()->with('success', 'Maintenance mode DISABLED. Site is live.');
    }
}
