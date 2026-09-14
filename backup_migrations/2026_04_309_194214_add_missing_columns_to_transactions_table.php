<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('transactions', 'status')) {
                $table->string('status')->default('completed')->after('amount');
            }
            if (!Schema::hasColumn('transactions', 'reference')) {
                $table->string('reference')->nullable()->after('description');
            }
            if (!Schema::hasColumn('transactions', 'wallet_id')) {
                $table->foreignId('wallet_id')->nullable()->after('user_id')->constrained()->onDelete('set null');
            }
        });
    }

    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['status', 'reference', 'wallet_id']);
        });
    }
};
