<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Laravel's Notifiable trait requires this table.
 * Not created by Laravel's default 0001_* migration set.
 * ViewServiceProvider uses it to load unread notifications for the navbar.
 *
 * NOTE: $table->morphs('notifiable') already creates:
 *   - notifiable_type column
 *   - notifiable_id column
 *   - index notifications_notifiable_type_notifiable_id_index
 * Do NOT add an explicit ->index(['notifiable_type', 'notifiable_id'])
 * or PostgreSQL rejects it as a duplicate (SQLSTATE[42P07]).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('type');
                $table->morphs('notifiable');   // ← this auto-creates the index
                $table->text('data');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
                // REMOVED: $table->index(['notifiable_type', 'notifiable_id']);
                // was duplicate of what morphs() already creates
            });
        }
    }

    public function down(): void
    {
        // Keep notifications — may contain real user data
    }
};
