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

    public function clearCache()
    {
        Artisan::call('optimize:clear');
        return back()->with('success', 'System cache cleared successfully.');
    }

    public function toggleMaintenance(Request $request)
    {
        $currentMode = Setting::get('maintenance_mode', '0');
        $newMode = $currentMode === '1' ? '0' : '1';
        
        Setting::set('maintenance_mode', $newMode, 'boolean');

        if ($newMode === '1') {
            // Generate a secret bypass link so admins can still access the site
            Artisan::call('down', ['--secret' => 'racksephnox-admin']);
            return back()->with('success', 'Maintenance mode ENABLED. Bypass URL: yourdomain.com/racksephnox-admin');
        } else {
            Artisan::call('up');
            return back()->with('success', 'Maintenance mode DISABLED. Site is live.');
        }
    }
}
