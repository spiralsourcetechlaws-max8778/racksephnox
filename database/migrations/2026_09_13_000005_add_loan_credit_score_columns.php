<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * LoanCreditScore::$fillable requires:
 *   tier, total_borrowed, total_repaid,
 *   on_time_payments, late_payments, defaults
 *
 * 000004 omitted them. LoanCreditService::recalculate() inserts 'tier'
 * on firstOrCreate and updates all six thereafter, so every dashboard
 * load 500s with SQLSTATE[42703] Undefined column.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('loan_credit_scores')) {
            return;
        }

        Schema::table('loan_credit_scores', function (Blueprint $t) {
            if (! Schema::hasColumn('loan_credit_scores', 'tier')) {
                $t->string('tier', 16)->nullable()->after('score');
            }
            if (! Schema::hasColumn('loan_credit_scores', 'total_borrowed')) {
                $t->decimal('total_borrowed', 15, 2)->default(0);
            }
            if (! Schema::hasColumn('loan_credit_scores', 'total_repaid')) {
                $t->decimal('total_repaid', 15, 2)->default(0);
            }
            if (! Schema::hasColumn('loan_credit_scores', 'on_time_payments')) {
                $t->integer('on_time_payments')->default(0);
            }
            if (! Schema::hasColumn('loan_credit_scores', 'late_payments')) {
                $t->integer('late_payments')->default(0);
            }
            if (! Schema::hasColumn('loan_credit_scores', 'defaults')) {
                $t->integer('defaults')->default(0);
            }
        });
    }

    public function down(): void
    {
        // Keep — data lives here
    }
};
