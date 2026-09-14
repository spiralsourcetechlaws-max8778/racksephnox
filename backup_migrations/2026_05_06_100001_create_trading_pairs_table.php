<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up() {
        Schema::create('trading_pairs', function (Blueprint $table) {
            $table->id();
            $table->string('symbol', 10)->unique();
            $table->string('base_currency', 5);
            $table->string('quote_currency', 5);
            $table->decimal('min_trade_amount', 15, 8)->default(0.0001);
            $table->decimal('max_trade_amount', 15, 8)->default(100);
            $table->decimal('tick_size', 15, 8)->default(0.0001);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }
    public function down() { Schema::dropIfExists('trading_pairs'); }
};
