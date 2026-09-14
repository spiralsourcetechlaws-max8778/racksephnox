<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('lottery_revenue_targets', function (Blueprint $table) {
            $table->id();
            $table->decimal('target_amount', 12, 2)->default(88000);
            $table->decimal('current_revenue', 12, 2)->default(0);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('lottery_revenue_targets');
    }
};
