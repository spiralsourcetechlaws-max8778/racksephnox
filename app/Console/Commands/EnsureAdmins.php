<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Promotes every email in ADMIN_EMAILS (comma-separated) to super_admin.
 * Idempotent. No-op if the env var isn't set. Called from entrypoint.sh
 * so promotion happens on every deploy, no shell required.
 */
class EnsureAdmins extends Command
{
    protected $signature = 'admin:ensure';
    protected $description = 'Promote ADMIN_EMAILS to super_admin';

    public function handle(): int
    {
        $raw = (string) env('ADMIN_EMAILS', '');
        if ($raw === '') {
            $this->line('ADMIN_EMAILS not set — nothing to do.');
            return self::SUCCESS;
        }

        if (!Schema::hasTable('users')) {
            $this->warn('users table missing — aborting.');
            return self::FAILURE;
        }

        $hasAdminRole = Schema::hasColumn('users', 'admin_role');
        $emails = array_filter(array_map('trim', explode(',', $raw)));

        foreach ($emails as $email) {
            $update = ['is_admin' => true];
            if ($hasAdminRole) {
                $update['admin_role'] = 'super_admin';
            }

            $affected = DB::table('users')->where('email', $email)->update($update);

            if ($affected > 0) {
                $this->info("✅ Promoted {$email} to super_admin");
            } else {
                $this->warn("⚠️  {$email} not found — nothing promoted");
            }
        }

        return self::SUCCESS;
    }
}
