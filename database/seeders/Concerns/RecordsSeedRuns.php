<?php

namespace Database\Seeders\Concerns;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

trait RecordsSeedRuns
{
    protected function recordSeedRun(
        string $seeder,
        string $status,
        ?string $target = null,
        ?int $durationMs = null,
        ?string $error = null
    ): void {
        // Non-fatal — do not let a logging failure break the seeder
        try {
            if (!Schema::hasTable('seed_runs')) {
                return;
            }

            DB::table('seed_runs')->insert([
                'run_at'      => now(),
                'seeder'      => $seeder,
                'status'      => $status,
                'target'      => $target ? mb_substr($target, 0, 128) : null,
                'duration_ms' => $durationMs,
                'error'       => $error ? mb_substr($error, 0, 2000) : null,
                'commit_sha'  => env('RENDER_GIT_COMMIT', env('GIT_COMMIT', null)),
                'environment' => app()->environment(),
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        } catch (\Throwable $e) {
            \Log::warning('seed_runs table write failed', ['original' => $e->getMessage()]);
        }
    }
}
