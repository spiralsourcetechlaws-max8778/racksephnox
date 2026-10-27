<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('machine_investments')) {
            Schema::table('machine_investments', function (Blueprint $table) {
                if (!Schema::hasColumn('machine_investments', 'total_projected_profit')) {
                    $table->decimal('total_projected_profit', 15, 2)->default(0)->after('daily_profit');
                }
                if (!Schema::hasColumn('machine_investments', 'last_accrued_at')) {
                    $table->timestamp('last_accrued_at')->nullable()->after('end_date');
                }
                if (!Schema::hasColumn('machine_investments', 'withdrawn')) {
                    $table->boolean('withdrawn')->default(false)->after('profit_credited');
                }
                if (!Schema::hasColumn('machine_investments', 'early_withdrawn')) {
                    $table->boolean('early_withdrawn')->default(false)->after('withdrawn');
                }
                if (!Schema::hasColumn('machine_investments', 'penalty_applied')) {
                    $table->decimal('penalty_applied', 15, 2)->default(0)->after('early_withdrawn');
                }
            });
        }

        if (Schema::hasTable('machines')) {
            Schema::table('machines', function (Blueprint $table) {
                if (!Schema::hasColumn('machines', 'vip1_start_amount')) {
                    $table->decimal('vip1_start_amount', 15, 2)->default(0);
                }
                if (!Schema::hasColumn('machines', 'vip2_start_amount')) {
                    $table->decimal('vip2_start_amount', 15, 2)->default(0);
                }
                if (!Schema::hasColumn('machines', 'vip3_start_amount')) {
                    $table->decimal('vip3_start_amount', 15, 2)->default(0);
                }
                if (!Schema::hasColumn('machines', 'duration_days')) {
                    $table->integer('duration_days')->default(14);
                }
                if (!Schema::hasColumn('machines', 'growth_rate')) {
                    $table->decimal('growth_rate', 8, 2)->default(0);
                }
            });
        }
    }

    public function down(): void {}
};
