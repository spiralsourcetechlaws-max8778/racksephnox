<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('wealth_tax_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('investment_id')->constrained('machine_investments')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('machine_id')->constrained('machines')->onDelete('cascade');
            $table->string('frequency'); // daily, weekly, monthly, yearly
            $table->decimal('profit_amount', 15, 2);
            $table->decimal('tax_amount', 15, 2);
            $table->decimal('tax_rate', 10, 6);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('wealth_tax_logs');
    }
};
