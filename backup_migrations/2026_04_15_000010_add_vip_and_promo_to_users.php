<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_vip')->default(false)->after('is_admin');
            $table->boolean('has_promo')->default(false)->after('is_vip');
            $table->timestamp('promo_expires_at')->nullable()->after('has_promo');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_vip', 'has_promo', 'promo_expires_at']);
        });
    }
};
