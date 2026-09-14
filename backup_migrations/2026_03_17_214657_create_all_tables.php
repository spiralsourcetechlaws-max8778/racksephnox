<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Create tables in order (respect foreign keys)

        // 1. users
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
            $table->boolean('is_admin')->default(false);
            $table->string('kyc_level')->default('basic');
            $table->boolean('is_verified')->default(false);
            $table->rememberToken();
            $table->timestamps();
        });

        // 2. wallets
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('balance', 15, 2)->default(0);
            $table->decimal('locked_balance', 15, 2)->default(0);
            $table->timestamps();

            $table->index('user_id');
        });

        // 3. investment_plans
        Schema::create('investment_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->decimal('min_amount', 15, 2);
            $table->decimal('max_amount', 15, 2);
            $table->decimal('daily_interest_rate', 5, 2);
            $table->integer('duration_days');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('is_active');
        });

        // 4. investments
        Schema::create('investments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('plan_id')->constrained('investment_plans');
            $table->decimal('amount', 15, 2);
            $table->decimal('daily_profit', 15, 2);
            $table->decimal('total_projected_profit', 15, 2);
            $table->integer('remaining_days');
            $table->enum('status', ['active', 'completed', 'cancelled'])->default('active');
            $table->timestamp('start_date');
            $table->timestamp('end_date');
            $table->timestamp('last_accrued_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('end_date');
        });

        // 5. transactions
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('wallet_id')->constrained()->onDelete('cascade');
            $table->string('type');
            $table->decimal('amount', 15, 2);
            $table->decimal('balance_after', 15, 2);
            $table->string('description')->nullable();
            $table->string('reference')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index('reference');
        });

        // 6. mpesa_transactions
        Schema::create('mpesa_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('transaction_type');
            $table->string('transaction_id')->nullable();
            $table->decimal('amount', 15, 2);
            $table->string('phone');
            $table->string('reference');
            $table->string('description')->nullable();
            $table->enum('status', ['pending', 'completed', 'failed'])->default('pending');
            $table->string('mpesa_receipt_number')->nullable();
            $table->timestamp('transaction_date')->nullable();
            $table->json('raw_callback_data')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('reference');
            $table->index('mpesa_receipt_number');
        });

        // 7. kyc_documents
        Schema::create('kyc_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('document_type');
            $table->string('document_path');
            $table->enum('status', ['pending', 'verified', 'rejected'])->default('pending');
            $table->timestamp('verified_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        // 8. audit_logs
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('url')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index('action');
        });

        // 9. crypto_prices
        Schema::create('crypto_prices', function (Blueprint $table) {
            $table->id();
            $table->string('symbol', 10)->unique();
            $table->string('name');
            $table->decimal('price_usd', 20, 8);
            $table->decimal('price_kes', 20, 2);
            $table->timestamp('last_updated');
            $table->timestamps();

            $table->index('last_updated');
        });
    }

    public function down()
    {
        // Drop in reverse order (respect foreign keys)
        Schema::dropIfExists('crypto_prices');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('kyc_documents');
        Schema::dropIfExists('mpesa_transactions');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('investments');
        Schema::dropIfExists('investment_plans');
        Schema::dropIfExists('wallets');
        Schema::dropIfExists('users');
    }
};
