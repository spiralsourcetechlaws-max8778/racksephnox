<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('btc_price_history', function (Blueprint $table) {
            // Check if columns exist before adding
            if (!Schema::hasColumn('btc_price_history', 'price_kes')) {
                $table->decimal('price_kes', 15, 2)->after('id');
            }
            if (!Schema::hasColumn('btc_price_history', 'recorded_at')) {
                $table->timestamp('recorded_at')->nullable()->after('price_kes');
            }
        });
    }

    public function down()
    {
        Schema::table('btc_price_history', function (Blueprint $table) {
            $table->dropColumn(['price_kes', 'recorded_at']);
        });
    }
};
