<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('crypto_prices', function (Blueprint $table) {
            $table->decimal('percent_change_24h', 8, 2)->nullable()->after('price_kes');
        });
    }

    public function down()
    {
        Schema::table('crypto_prices', function (Blueprint $table) {
            $table->dropColumn('percent_change_24h');
        });
    }
};
