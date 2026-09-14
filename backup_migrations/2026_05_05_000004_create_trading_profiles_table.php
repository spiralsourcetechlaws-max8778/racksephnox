<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('trading_profiles')) {
            Schema::create('trading_profiles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('username')->unique();
                $table->text('bio')->nullable();
                $table->string('avatar')->nullable();
                $table->boolean('is_public')->default(true);
                $table->boolean('allow_copy_trading')->default(true);
                $table->decimal('total_pnl', 15, 2)->default(0);
                $table->decimal('win_rate', 5, 2)->default(0);
                $table->integer('total_trades')->default(0);
                $table->integer('followers_count')->default(0);
                $table->integer('following_count')->default(0);
                $table->timestamps();
                $table->index('username');
            });
        }
    }
    public function down() { Schema::dropIfExists('trading_profiles'); }
};
