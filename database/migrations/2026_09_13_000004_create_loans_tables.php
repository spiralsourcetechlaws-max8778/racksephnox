<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * LOANS FRAMEWORK — full table set
 *
 * These tables were only ever created by 2026_03_17_214657_create_all_tables.php,
 * which db-setup.sh moved to backup_migrations/ — so they never shipped to Render.
 *
 * DashboardController queries:
 *   Loan::with('product')->where('user_id', ...)->latest()->get()
 *   LoanRepayment::with('loan.product')->where('user_id',...)->where('status','pending')
 *
 * All 6 tables created here, in FK-safe order, guarded by hasTable for local safety.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ─── 1. LOAN PRODUCTS ─────────────────────────────────────
        if (! Schema::hasTable('loan_products')) {
            Schema::create('loan_products', function (Blueprint $t) {
                $t->id();
                $t->string('name');
                $t->string('code', 32)->nullable()->unique();
                $t->text('description')->nullable();
                $t->decimal('min_amount', 15, 2)->default(0);
                $t->decimal('max_amount', 15, 2)->default(0);
                $t->decimal('interest_rate', 8, 4)->default(0);
                $t->string('interest_method', 16)->default('flat');   // flat | reducing
                $t->integer('min_duration_days')->default(7);
                $t->integer('max_duration_days')->default(365);
                $t->boolean('is_active')->default(true);
                $t->timestamps();
            });
        }

        // ─── 2. LOANS ─────────────────────────────────────────────
        if (! Schema::hasTable('loans')) {
            Schema::create('loans', function (Blueprint $t) {
                $t->id();
                $t->string('reference', 64)->unique();
                $t->unsignedBigInteger('user_id');
                $t->unsignedBigInteger('loan_product_id')->nullable();

                $t->decimal('principal', 15, 2)->default(0);
                $t->decimal('interest_rate', 8, 4)->default(0);
                $t->string('interest_method', 16)->default('flat');
                $t->decimal('total_interest', 15, 2)->default(0);
                $t->decimal('total_payable', 15, 2)->default(0);
                $t->decimal('amount_paid', 15, 2)->default(0);
                $t->decimal('balance', 15, 2)->default(0);
                $t->decimal('late_fees', 15, 2)->default(0);

                $t->integer('duration_days')->default(30);
                $t->string('repayment_frequency', 16)->default('monthly');
                $t->integer('installments')->default(1);
                $t->decimal('installment_amount', 15, 2)->default(0);

                $t->string('status', 16)->default('pending');
                $t->text('purpose')->nullable();

                $t->unsignedBigInteger('approved_by')->nullable();
                $t->timestamp('approved_at')->nullable();
                $t->text('rejected_reason')->nullable();
                $t->timestamp('disbursed_at')->nullable();
                $t->date('first_due_date')->nullable();
                $t->date('next_due_date')->nullable();
                $t->timestamp('last_payment_at')->nullable();
                $t->timestamp('closed_at')->nullable();

                $t->integer('credit_score_at_application')->nullable();

                $t->timestamps();

                $t->index('user_id');
                $t->index('status');
                $t->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $t->foreign('loan_product_id')->references('id')->on('loan_products')->onDelete('set null');
            });
        }

        // ─── 3. LOAN REPAYMENTS ───────────────────────────────────
        if (! Schema::hasTable('loan_repayments')) {
            Schema::create('loan_repayments', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('loan_id');
                $t->unsignedBigInteger('user_id')->nullable();
                $t->integer('installment_number')->default(1);
                $t->decimal('amount_due', 15, 2)->default(0);
                $t->decimal('amount_paid', 15, 2)->default(0);
                $t->date('due_date')->nullable();
                $t->timestamp('paid_at')->nullable();
                $t->string('status', 16)->default('pending');    // pending | paid | overdue
                $t->decimal('late_fee', 15, 2)->default(0);
                $t->text('notes')->nullable();
                $t->timestamps();
                $t->index('loan_id');
                $t->index('user_id');
                $t->index('due_date');
                $t->foreign('loan_id')->references('id')->on('loans')->onDelete('cascade');
            });
        }

        // ─── 4. LOAN GUARANTORS ───────────────────────────────────
        if (! Schema::hasTable('loan_guarantors')) {
            Schema::create('loan_guarantors', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('loan_id');
                $t->unsignedBigInteger('user_id')->nullable();
                $t->string('name');
                $t->string('phone', 32)->nullable();
                $t->string('relationship', 64)->nullable();
                $t->string('status', 16)->default('pending');
                $t->timestamps();
                $t->foreign('loan_id')->references('id')->on('loans')->onDelete('cascade');
            });
        }

        // ─── 5. LOAN COLLATERALS ──────────────────────────────────
        if (! Schema::hasTable('loan_collaterals')) {
            Schema::create('loan_collaterals', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('loan_id');
                $t->string('type', 32);
                $t->text('description')->nullable();
                $t->decimal('estimated_value', 15, 2)->default(0);
                $t->string('status', 16)->default('pending');
                $t->timestamps();
                $t->foreign('loan_id')->references('id')->on('loans')->onDelete('cascade');
            });
        }

        // ─── 6. LOAN CREDIT SCORES ────────────────────────────────
        if (! Schema::hasTable('loan_credit_scores')) {
            Schema::create('loan_credit_scores', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('user_id');
                $t->integer('score')->default(500);
                $t->json('factors')->nullable();
                $t->timestamp('last_calculated_at')->nullable();
                $t->timestamps();
                $t->index('user_id');
                $t->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        // Keep loans tables — may contain real financial data
    }
};
