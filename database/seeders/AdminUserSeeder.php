<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    /**
     * Create/update the primary admin. Production refuses to run unless
     * SEED_ADMIN_PASSWORD is explicitly set — prevents the previous
     * "admin123 in public GitHub" incident.
     */
    public function run(): void
    {
        $email    = env('SEED_ADMIN_EMAIL', 'admin@racksephnox.com');
        $password = env('SEED_ADMIN_PASSWORD');

        if (app()->environment('production') && !$password) {
            $this->command->warn('⏭  SEED_ADMIN_PASSWORD not set — skipping admin seed.');
            $this->command->warn('   Set it in Render Environment then redeploy if you');
            $this->command->warn('   want the seeder to create/rotate the admin password.');
            return;
        }

        // Local fallback: ephemeral random password, printed once
        $password = $password ?: Str::random(24);
        if (!app()->environment('production')) {
            $this->command->info('   Local admin password: ' . $password);
        }

        $admin = User::firstOrNew(['email' => $email]);

        $admin->fill([
            'name'              => 'Super Admin',
            'phone'             => '+254711111111',
            'password'          => Hash::make($password),
            'is_admin'          => true,
            'admin_role'        => 'super_admin',
            'is_verified'       => true,
            'kyc_status'        => 'verified',
            'email_verified_at' => now(),
        ]);

        // Referral code only generated on first creation — never rotated
        if (!$admin->exists) {
            $admin->referral_code = strtoupper(Str::random(8));
        }

        $admin->save();

        if (!$admin->wallet) {
            $admin->wallet()->create(['balance' => 100000]);
        }

        $this->command->info('✅ Admin user ready: ' . $email);
    }
}
