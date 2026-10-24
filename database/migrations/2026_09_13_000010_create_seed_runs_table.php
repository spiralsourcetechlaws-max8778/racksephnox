<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Persistent record of every seeder execution, per deploy.
 * Enables post-mortem queries like:
 *   SELECT * FROM seed_runs WHERE status='failed' ORDER BY run_at DESC LIMIT 20;
 * After the fact, from prod — no shell required (query via a future admin route).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('seed_runs')) {
            return;
        }

        Schema::create('seed_runs', function (Blueprint $t) {
            $t->id();
            $t->timestamp('run_at')->useCurrent();
            $t->string('seeder', 128);
            $t->string('status', 16);                    // ok | failed | skipped
            $t->string('target', 128)->nullable();       // machine code, row key, etc.
            $t->integer('duration_ms')->nullable();
            $t->text('error')->nullable();
            $t->string('commit_sha', 40)->nullable();
            $t->string('environment', 32)->nullable();
            $t->timestamps();

            $t->index('status');
            $t->index('seeder');
            $t->index('run_at');
        });
    }

    public function down(): void {}
};
