<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up() {
        Schema::table('trade_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('trade_orders', 'pair_id')) $table->foreignId('pair_id')->nullable()->constrained('trading_pairs');
            if (!Schema::hasColumn('trade_orders', 'take_profit_price')) $table->decimal('take_profit_price', 15, 2)->nullable();
            if (!Schema::hasColumn('trade_orders', 'stop_loss_price')) $table->decimal('stop_loss_price', 15, 2)->nullable();
            if (!Schema::hasColumn('trade_orders', 'time_in_force')) $table->enum('time_in_force', ['GTC', 'IOC', 'FOK'])->default('GTC');
            if (!Schema::hasColumn('trade_orders', 'expires_at')) $table->timestamp('expires_at')->nullable();
        });
    }
    public function down() {
        Schema::table('trade_orders', function (Blueprint $table) {
            $table->dropColumn(['pair_id', 'take_profit_price', 'stop_loss_price', 'time_in_force', 'expires_at']);
        });
    }
};
