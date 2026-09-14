<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // =====================================================
        // 1. NEW TABLE: lottery_jackpot_pools (4-tier system)
        // =====================================================
        if (!Schema::hasTable('lottery_jackpot_pools')) {
            Schema::create('lottery_jackpot_pools', function (Blueprint $table) {
                $table->id();
                $table->string('tier')->unique();                 // bronze | silver | gold | cosmic
                $table->string('frequency_hz')->default('528');   // 528 | 639 | 741 | 963
                $table->decimal('seed_amount', 18, 2)->default(0);
                $table->decimal('current_pool', 18, 2)->default(0);
                $table->decimal('ceiling_amount', 18, 2)->nullable();
                $table->decimal('contribution_rate', 5, 2)->default(1.0);
                $table->integer('wins_count')->default(0);
                $table->timestamp('last_won_at')->nullable();
                $table->string('last_winner_id')->nullable();
                $table->boolean('must_drop')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });

            // Seed the four tiers with their sacred values
            DB::table('lottery_jackpot_pools')->insert([
                ['tier' => 'bronze', 'frequency_hz' => '528', 'seed_amount' => 10_000,      'current_pool' => 10_000,      'ceiling_amount' => 500_000,     'contribution_rate' => 1.0, 'created_at' => now(), 'updated_at' => now()],
                ['tier' => 'silver', 'frequency_hz' => '639', 'seed_amount' => 100_000,     'current_pool' => 100_000,     'ceiling_amount' => 5_000_000,   'contribution_rate' => 1.5, 'created_at' => now(), 'updated_at' => now()],
                ['tier' => 'gold',   'frequency_hz' => '741', 'seed_amount' => 1_000_000,   'current_pool' => 1_000_000,   'ceiling_amount' => 50_000_000,  'contribution_rate' => 2.0, 'created_at' => now(), 'updated_at' => now()],
                ['tier' => 'cosmic', 'frequency_hz' => '963', 'seed_amount' => 10_000_000,  'current_pool' => 10_000_000,  'ceiling_amount' => 100_000_000, 'contribution_rate' => 3.0, 'created_at' => now(), 'updated_at' => now()],
            ]);
        }

        // =====================================================
        // 2. NEW TABLE: lottery_responsible_gaming
        // =====================================================
        if (!Schema::hasTable('lottery_responsible_gaming')) {
            Schema::create('lottery_responsible_gaming', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');
                $table->decimal('daily_loss_cap', 15, 2)->nullable();
                $table->decimal('weekly_loss_cap', 15, 2)->nullable();
                $table->decimal('monthly_loss_cap', 15, 2)->nullable();
                $table->decimal('daily_loss_used', 15, 2)->default(0);
                $table->decimal('weekly_loss_used', 15, 2)->default(0);
                $table->decimal('monthly_loss_used', 15, 2)->default(0);
                $table->integer('session_timeout_minutes')->default(60);
                $table->integer('reality_check_minutes')->default(30);
                $table->integer('cool_down_minutes')->default(0);
                $table->timestamp('cool_down_until')->nullable();
                $table->timestamp('self_exclusion_until')->nullable();
                $table->string('self_exclusion_reason')->nullable();
                $table->timestamp('last_session_start')->nullable();
                $table->timestamps();
            });
        }

        // =====================================================
        // 3. NEW TABLE: lottery_fair_seeds (provably-fair chain)
        // =====================================================
        if (!Schema::hasTable('lottery_fair_seeds')) {
            Schema::create('lottery_fair_seeds', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('server_seed_hash')->index();
                $table->string('server_seed')->nullable();       // revealed after rotation
                $table->string('client_seed');
                $table->bigInteger('nonce')->default(0);
                $table->string('previous_hash')->nullable();
                $table->boolean('revealed')->default(false);
                $table->timestamp('revealed_at')->nullable();
                $table->timestamps();
                $table->index(['user_id', 'revealed']);
            });
        }

        // =====================================================
        // 4. NEW TABLE: lottery_jackpot_wins
        // =====================================================
        if (!Schema::hasTable('lottery_jackpot_wins')) {
            Schema::create('lottery_jackpot_wins', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('lottery_spin_id')->nullable()->constrained('lottery_spins')->onDelete('set null');
                $table->string('tier');
                $table->decimal('amount_won', 18, 2);
                $table->decimal('pool_before', 18, 2);
                $table->decimal('pool_after', 18, 2);
                $table->boolean('paid')->default(false);
                $table->timestamp('paid_at')->nullable();
                $table->timestamps();
                $table->index(['user_id', 'tier']);
            });
        }

        // =====================================================
        // 5. NEW TABLE: lottery_currency_rates
        // =====================================================
        if (!Schema::hasTable('lottery_currency_rates')) {
            Schema::create('lottery_currency_rates', function (Blueprint $table) {
                $table->id();
                $table->string('currency', 3)->unique();
                $table->string('name');
                $table->string('symbol', 8);
                $table->decimal('rate_to_kes', 15, 6);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });

            DB::table('lottery_currency_rates')->insert([
                ['currency' => 'KES', 'name' => 'Kenyan Shilling',  'symbol' => 'KSh', 'rate_to_kes' => 1.0,       'created_at' => now(), 'updated_at' => now()],
                ['currency' => 'USD', 'name' => 'US Dollar',         'symbol' => '$',   'rate_to_kes' => 130.0,     'created_at' => now(), 'updated_at' => now()],
                ['currency' => 'EUR', 'name' => 'Euro',              'symbol' => '€',   'rate_to_kes' => 142.0,     'created_at' => now(), 'updated_at' => now()],
                ['currency' => 'GBP', 'name' => 'British Pound',     'symbol' => '£',   'rate_to_kes' => 165.0,     'created_at' => now(), 'updated_at' => now()],
                ['currency' => 'ZAR', 'name' => 'South African Rand','symbol' => 'R',   'rate_to_kes' => 7.0,       'created_at' => now(), 'updated_at' => now()],
                ['currency' => 'NGN', 'name' => 'Nigerian Naira',    'symbol' => '₦',   'rate_to_kes' => 0.085,     'created_at' => now(), 'updated_at' => now()],
                ['currency' => 'INR', 'name' => 'Indian Rupee',      'symbol' => '₹',   'rate_to_kes' => 1.55,      'created_at' => now(), 'updated_at' => now()],
            ]);
        }

        // =====================================================
        // 6. Add columns to existing lottery_spins if missing
        // =====================================================
        if (Schema::hasTable('lottery_spins')) {
            $this->addColumn('lottery_spins', 'jackpot_tier', 'string', ['nullable' => true]);
            $this->addColumn('lottery_spins', 'client_seed', 'string', ['nullable' => true]);
            $this->addColumn('lottery_spins', 'server_seed_hash', 'string', ['nullable' => true]);
            $this->addColumn('lottery_spins', 'nonce', 'bigInteger', ['default' => 0]);
            $this->addColumn('lottery_spins', 'currency', 'string', ['default' => 'KES']);
            $this->addColumn('lottery_spins', 'bet_in_kes', 'decimal', ['precision' => 15, 'scale' => 2, 'default' => 0]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('lottery_jackpot_wins');
        Schema::dropIfExists('lottery_currency_rates');
        Schema::dropIfExists('lottery_fair_seeds');
        Schema::dropIfExists('lottery_responsible_gaming');
        Schema::dropIfExists('lottery_jackpot_pools');
    }

    private function addColumn(string $table, string $column, string $type, array $options = []): void
    {
        if (Schema::hasColumn($table, $column)) return;

        Schema::table($table, function (Blueprint $t) use ($column, $type, $options) {
            $col = match ($type) {
                'string'     => $t->string($column),
                'bigInteger' => $t->bigInteger($column),
                'decimal'    => $t->decimal($column, $options['precision'] ?? 15, $options['scale'] ?? 2),
                default      => $t->string($column),
            };

            if ($options['nullable'] ?? false) $col->nullable();
            if (isset($options['default']))    $col->default($options['default']);
        });
    }
};
