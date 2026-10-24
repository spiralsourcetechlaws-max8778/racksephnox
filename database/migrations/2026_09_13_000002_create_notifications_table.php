<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Laravel's Notifiable trait requires this table.
 * Not created by Laravel's default 0001_* migration set.
 * ViewServiceProvider uses it to load unread notifications for the navbar.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('type');
                $table->morphs('notifiable');
                $table->text('data');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
                $table->index(['notifiable_type', 'notifiable_id']);
            });
        }
    }

    public function down(): void
    {
        // Keep notifications — they may contain real user data
    }
};
