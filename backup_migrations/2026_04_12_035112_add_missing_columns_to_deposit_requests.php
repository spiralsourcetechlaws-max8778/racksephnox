<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('deposit_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('deposit_requests', 'phone_number')) {
                $table->string('phone_number')->nullable()->after('user_id');
            }
            if (!Schema::hasColumn('deposit_requests', 'transaction_code')) {
                $table->string('transaction_code')->nullable()->after('amount');
            }
        });
    }

    public function down()
    {
        Schema::table('deposit_requests', function (Blueprint $table) {
            $table->dropColumn(['phone_number', 'transaction_code']);
        });
    }
};
