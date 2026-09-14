<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('trading_accounts')) {
            Schema::create('trading_accounts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->decimal('balance', 15, 2)->default(0);
                $table->decimal('locked_balance', 15, 2)->default(0);
                $table->decimal('btc_balance', 15, 8)->default(0);
                $table->timestamps();
                $table->index('user_id');
            });
        }
    }
    public function down() { Schema::dropIfExists('trading_accounts'); }
};
