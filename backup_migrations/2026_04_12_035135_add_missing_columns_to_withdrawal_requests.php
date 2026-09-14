<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('withdrawal_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('withdrawal_requests', 'fee')) {
                $table->decimal('fee', 10, 2)->default(0)->after('amount');
            }
            if (!Schema::hasColumn('withdrawal_requests', 'bank_account_id')) {
                $table->foreignId('bank_account_id')->nullable()->after('fee')->constrained('user_bank_accounts');
            }
        });
    }

    public function down()
    {
        Schema::table('withdrawal_requests', function (Blueprint $table) {
            $table->dropColumn(['fee', 'bank_account_id']);
        });
    }
};
