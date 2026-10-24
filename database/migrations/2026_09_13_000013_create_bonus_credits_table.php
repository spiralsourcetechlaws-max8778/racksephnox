<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tracks every bonus credit applied to a user's wallet so it can only
 * fire once per event.
 *
 *   signup            → reference_id = user.id (recipient)
 *   referral          → reference_id = new_user.id (once per referred user)
 *   machine_activation → reference_id = machine_investment.id
 *
 * UNIQUE(bonus_type, reference_id) enforces idempotency at the DB level.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bonus_credits')) {
            return;
        }

        Schema::create('bonus_credits', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id');          // who received
            $t->string('bonus_type', 32);               // signup | referral | machine_activation
            $t->unsignedBigInteger('reference_id');     // see docblock
            $t->decimal('amount', 15, 2);
            $t->string('description')->nullable();
            $t->timestamp('credited_at')->useCurrent();
            $t->timestamps();

            $t->index('user_id');
            $t->index('bonus_type');
            $t->unique(['bonus_type', 'reference_id']);
        });
    }

    public function down(): void {}
};
