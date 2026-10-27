<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('trading_candles')) {
            return;
        }

        if (Schema::hasColumn('trading_candles', 'close_time')) {
            return;
        }

        Schema::table('trading_candles', function (Blueprint $t) {
            $t->timestamp('close_time')->nullable();
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('trading_candles') && Schema::hasColumn('trading_candles', 'close_time')) {
            Schema::table('trading_candles', function (Blueprint $t) {
                $t->dropColumn('close_time');
            });
        }
    }
};
