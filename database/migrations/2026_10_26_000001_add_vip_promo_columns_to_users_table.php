<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (! Schema::hasColumn('users', 'is_vip')) {
                    $table->boolean('is_vip')->default(false)->after('is_verified');
                }
                if (! Schema::hasColumn('users', 'has_promo')) {
                    $table->boolean('has_promo')->default(false)->after('is_vip');
                }
                if (! Schema::hasColumn('users', 'promo_expires_at')) {
                    $table->timestamp('promo_expires_at')->nullable()->after('has_promo');
                }
            });
        }
    }

    public function down(): void
    {
        // Additive only
    }
};
