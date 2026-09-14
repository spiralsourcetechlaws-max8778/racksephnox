<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('investment_plans', function (Blueprint $table) {
            // Add columns if they don't exist
            if (!Schema::hasColumn('investment_plans', 'allow_auto_reinvest')) {
                $table->boolean('allow_auto_reinvest')->default(false)->after('is_active');
            }
            if (!Schema::hasColumn('investment_plans', 'allow_early_withdrawal')) {
                $table->boolean('allow_early_withdrawal')->default(true)->after('allow_auto_reinvest');
            }
            if (!Schema::hasColumn('investment_plans', 'early_withdrawal_penalty')) {
                $table->decimal('early_withdrawal_penalty', 5, 2)->default(20)->after('allow_early_withdrawal');
            }
            if (!Schema::hasColumn('investment_plans', 'max_reinvestment_cycles')) {
                $table->integer('max_reinvestment_cycles')->default(1)->after('early_withdrawal_penalty');
            }
            if (!Schema::hasColumn('investment_plans', 'is_infinite')) {
                $table->boolean('is_infinite')->default(false)->after('max_reinvestment_cycles');
            }
        });
    }

    public function down()
    {
        Schema::table('investment_plans', function (Blueprint $table) {
            $table->dropColumn([
                'allow_auto_reinvest',
                'allow_early_withdrawal',
                'early_withdrawal_penalty',
                'max_reinvestment_cycles',
                'is_infinite',
            ]);
        });
    }
};
