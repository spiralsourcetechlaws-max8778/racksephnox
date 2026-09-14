<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Skip if table doesn't exist
        if (!Schema::hasTable('trade_orders')) {
            return;
        }
        
        // Use raw SQLite statements to avoid Laravel's schema builder issues
        $columns = [
            'order_type' => "ALTER TABLE trade_orders ADD COLUMN order_type TEXT DEFAULT 'market'",
            'stop_price' => "ALTER TABLE trade_orders ADD COLUMN stop_price REAL",
            'filled_amount' => "ALTER TABLE trade_orders ADD COLUMN filled_amount REAL DEFAULT 0",
            'filled_kes' => "ALTER TABLE trade_orders ADD COLUMN filled_kes REAL DEFAULT 0"
        ];
        
        foreach ($columns as $column => $sql) {
            try {
                $exists = DB::select("PRAGMA table_info(trade_orders)");
                $columnsExist = array_column($exists, 'name');
                if (!in_array($column, $columnsExist)) {
                    DB::statement($sql);
                }
            } catch (\Exception $e) {
                // Column might already exist
            }
        }
    }

    public function down()
    {
        // SQLite doesn't support dropping columns easily
        // Skip this for simplicity
    }
};
