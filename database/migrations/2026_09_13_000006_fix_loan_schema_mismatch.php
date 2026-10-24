<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 000004 guessed at loan_products, loan_repayments, loan_guarantors,
 * loan_collaterals, loan_credit_scores because the SQLite source-of-truth
 * (2026_03_17_214657_create_all_tables.php) was in backup_migrations/.
 *
 * Every column name below has been verified against database.sqlite,
 * which is what the app's Eloquent models were written against.
 *
 * Prod loan tables are empty (app was 500ing before data could land), so
 * ALTER-based fixes are safe. All ops are hasColumn-guarded for reruns.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ─── loan_products: add 9 missing columns ────────────────
        if (Schema::hasTable('loan_products')) {
            Schema::table('loan_products', function (Blueprint $t) {
                if (! Schema::hasColumn('loan_products', 'slug')) {
                    $t->string('slug')->nullable();  // UNIQUE added below
                }
                if (! Schema::hasColumn('loan_products', 'icon')) {
                    $t->string('icon')->nullable()->default('fa-hand-holding-usd');
                }
                if (! Schema::hasColumn('loan_products', 'color')) {
                    $t->string('color')->nullable()->default('from-gold-400 to-amber-500');
                }
                if (! Schema::hasColumn('loan_products', 'grace_period_days')) {
                    $t->integer('grace_period_days')->default(3);
                }
                if (! Schema::hasColumn('loan_products', 'late_fee_rate')) {
                    $t->decimal('late_fee_rate', 5, 2)->default(5.00);
                }
                if (! Schema::hasColumn('loan_products', 'requires_guarantor')) {
                    $t->boolean('requires_guarantor')->default(false);
                }
                if (! Schema::hasColumn('loan_products', 'requires_collateral')) {
                    $t->boolean('requires_collateral')->default(false);
                }
                if (! Schema::hasColumn('loan_products', 'min_credit_score')) {
                    $t->integer('min_credit_score')->default(0);
                }
                if (! Schema::hasColumn('loan_products', 'frequency_hz')) {
                    $t->integer('frequency_hz')->default(528);
                }
            });

            // UNIQUE index on slug (SQLite had UNIQUE NOT NULL)
            try {
                Schema::table('loan_products', function (Blueprint $t) {
                    $t->unique('slug');
                });
            } catch (\Throwable $e) {
                // index already exists, or existing rows collide — non-fatal
            }
        }

        // ─── loan_repayments: rename 2 columns, add 4 ────────────
        if (Schema::hasTable('loan_repayments')) {
            if (Schema::hasColumn('loan_repayments', 'amount_due')
                && ! Schema::hasColumn('loan_repayments', 'amount')) {
                Schema::table('loan_repayments', function (Blueprint $t) {
                    $t->renameColumn('amount_due', 'amount');
                });
            }

            if (Schema::hasColumn('loan_repayments', 'late_fee')
                && ! Schema::hasColumn('loan_repayments', 'late_fee_portion')) {
                Schema::table('loan_repayments', function (Blueprint $t) {
                    $t->renameColumn('late_fee', 'late_fee_portion');
                });
            }

            Schema::table('loan_repayments', function (Blueprint $t) {
                if (! Schema::hasColumn('loan_repayments', 'principal_portion')) {
                    $t->decimal('principal_portion', 15, 2)->default(0);
                }
                if (! Schema::hasColumn('loan_repayments', 'interest_portion')) {
                    $t->decimal('interest_portion', 15, 2)->default(0);
                }
                if (! Schema::hasColumn('loan_repayments', 'balance_after')) {
                    $t->decimal('balance_after', 15, 2)->default(0);
                }
                if (! Schema::hasColumn('loan_repayments', 'reference')) {
                    $t->string('reference', 64)->nullable();
                }
            });
        }

        // ─── loan_guarantors: add 2 missing columns ──────────────
        if (Schema::hasTable('loan_guarantors')) {
            Schema::table('loan_guarantors', function (Blueprint $t) {
                if (! Schema::hasColumn('loan_guarantors', 'amount_guaranteed')) {
                    $t->decimal('amount_guaranteed', 15, 2)->default(0);
                }
                if (! Schema::hasColumn('loan_guarantors', 'accepted_at')) {
                    $t->timestamp('accepted_at')->nullable();
                }
            });
        }

        // ─── loan_collaterals: add 1 missing column ──────────────
        if (Schema::hasTable('loan_collaterals')) {
            Schema::table('loan_collaterals', function (Blueprint $t) {
                if (! Schema::hasColumn('loan_collaterals', 'document_path')) {
                    $t->string('document_path')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        // Keep — additive fixes only
    }
};
