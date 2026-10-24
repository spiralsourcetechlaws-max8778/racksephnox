<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    public function run()
    {
        $email = env('SEED_ADMIN_EMAIL', 'admin@racksephnox.com');
        $password = env('SEED_ADMIN_PASSWORD');

        // Never ship a known credential to production.
        if (app()->environment('production') && ! $password) {
            $this->command->warn('⏭  SEED_ADMIN_PASSWORD not set — skipping admin user seed.');
            return;
        }

        // Fallback only fires in local/dev.
        $password = $password ?: Str::random(24);
        if (! app()->environment('production')) {
            $this->command->info('Local admin password: ' . $password);
        }

        $admin = User::updateOrCreate(
            ['email' => $email],
            [
                'name'              => 'Super Admin',
                'phone'             => '+254711111111',
                'referral_code'     => strtoupper(Str::random(8)),
                'password'          => Hash::make($password),
                'is_admin'          => true,
                'is_verified'       => true,
                'kyc_status'        => 'verified',
                'email_verified_at' => now(),
            ]
        );

        if (! $admin->wallet) {
            $admin->wallet()->create(['balance' => 100000]);
        }

        $this->command->info('✅ Admin user ready: ' . $email);
    }
}
