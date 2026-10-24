<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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
            $t->string('status', 16);
            $t->string('target', 128)->nullable();
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
