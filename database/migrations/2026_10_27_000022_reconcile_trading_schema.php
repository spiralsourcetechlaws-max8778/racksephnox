<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ---- trade_orders.amount_btc --------------------------------------
        // Declared in TradeOrder::$fillable, written by placeOrder/matchOrder,
        // but never created (bootstrap's hasTable() guard skipped it because
        // the table already existed from an older migration).
        if (Schema::hasTable('trade_orders')
            && ! Schema::hasColumn('trade_orders', 'amount_btc')) {
            Schema::table('trade_orders', function (Blueprint $t) {
                $t->decimal('amount_btc', 20, 8)->default(0);
            });
        }

        // ---- trading_accounts.total_pnl + currency -----------------------
        if (Schema::hasTable('trading_accounts')
            && ! Schema::hasColumn('trading_accounts', 'total_pnl')) {
            Schema::table('trading_accounts', function (Blueprint $t) {
                $t->decimal('total_pnl', 20, 8)->default(0);
            });
        }
        if (Schema::hasTable('trading_accounts')
            && ! Schema::hasColumn('trading_accounts', 'currency')) {
            Schema::table('trading_accounts', function (Blueprint $t) {
                $t->string('currency', 8)->default('USDT');
            });
        }
    }

    public function down(): void
    {
        // Columns are additive; leave them on rollback to preserve data.
        // Dropping would also break $fillable writes that expect them.
    }
};
