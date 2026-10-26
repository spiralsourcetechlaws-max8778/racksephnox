<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'admin_role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('admin_role', 32)->nullable()->after('is_admin');
            });

            // Backfill: any existing admin becomes super_admin
            DB::table('users')
                ->where('is_admin', true)
                ->whereNull('admin_role')
                ->update(['admin_role' => 'super_admin']);
        }
    }

    public function down(): void
    {
        // Additive only
    }
};
