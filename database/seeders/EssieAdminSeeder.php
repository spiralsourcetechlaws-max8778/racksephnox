<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class EssieAdminSeeder extends Seeder
{
    public function run(): void
    {
        // Guard: skip if already exists (idempotent)
        if (User::where('email', 'essiemaina@gmail.com')->exists()) {
            return;
        }

        $password = env('ESSIE_ADMIN_PASSWORD');
        if (!$password) {
            $this->command->warn('ESSIE_ADMIN_PASSWORD not set — skipping');
            return;
        }

        $user = User::create([
            'name'              => 'Essie Maina',
            'email'             => 'essiemaina@gmail.com',
            'phone'             => '254708499541',
            'password'          => Hash::make($password),
            'is_admin'          => true,
            'admin_role'        => 'super_admin',
            'is_verified'       => true,
            'email_verified_at' => now(),
            'kyc_status'        => 'verified',
            'is_vip'            => true,
        ]);

        if ($user->wallet) {
            $user->wallet->update(['balance' => 8000]);
        }

        $this->command->info('✅ Essie Maina seeded as super admin');
    }
}
