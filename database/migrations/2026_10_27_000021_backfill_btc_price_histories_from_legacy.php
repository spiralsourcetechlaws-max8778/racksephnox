<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Add `currency` to the canonical (plural) table
        if (Schema::hasTable('btc_price_histories')
            && ! Schema::hasColumn('btc_price_histories', 'currency')) {
            Schema::table('btc_price_histories', function (Blueprint $t) {
                $t->string('currency', 8)->default('USD');
            });
        }

        // 2) Backfill from the legacy singular table, only if:
        //    - the target table exists and is EMPTY
        //    - the legacy singular table exists and has rows
        if (! Schema::hasTable('btc_price_histories')
            || ! Schema::hasTable('btc_price_history')) {
            return;
        }

        $targetHasRows = DB::table('btc_price_histories')->exists();
        if ($targetHasRows) {
            return; // already populated — never overwrite
        }

        $legacyHasRows = DB::table('btc_price_history')->exists();
        if (! $legacyHasRows) {
            return;
        }

        // Copy only columns present in BOTH tables
        $targetCols = Schema::getColumnListing('btc_price_histories');
        $sourceCols = Schema::getColumnListing('btc_price_history');
        $common = array_values(array_diff(
            array_intersect($targetCols, $sourceCols),
            ['id'] // do not copy the primary key
        ));

        if (empty($common)) {
            return;
        }

        // Chunked copy so we don't blow memory on large histories
        DB::table('btc_price_history')
            ->select($common)
            ->orderBy('id')
            ->chunk(500, function ($rows) use ($common) {
                $payload = $rows->map(function ($r) use ($common) {
                    $out = [];
                    foreach ($common as $col) {
                        $out[$col] = is_object($r) ? ($r->{$col} ?? null) : (($r[$col] ?? null));
                    }
                    return $out;
                })->all();

                if (! empty($payload)) {
                    DB::table('btc_price_histories')->insert($payload);
                }
            });
    }

    public function down(): void
    {
        // Do not remove data on rollback — the 720 historic rows are valuable.
        // Only drop `currency` if we added it.
        if (Schema::hasTable('btc_price_histories')
            && Schema::hasColumn('btc_price_histories', 'currency')) {
            Schema::table('btc_price_histories', function (Blueprint $t) {
                $t->dropColumn('currency');
            });
        }
    }
};
