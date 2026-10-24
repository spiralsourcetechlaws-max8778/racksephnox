<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $t) {
                $t->uuid('id')->primary();
                $t->string('type');
                $t->morphs('notifiable');
                $t->text('data');
                $t->timestamp('read_at')->nullable();
                $t->timestamps();
            });
        }
    }

    public function down(): void
    {
        // Keep notifications — may contain real user data
    }
};
