<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('wealth_tax_logs')) {
            return;
        }

        Schema::create('wealth_tax_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('investment_id')->constrained('machine_investments')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('machine_id')->constrained('machines')->cascadeOnDelete();
            $table->string('frequency');
            $table->decimal('profit_amount', 15, 2);
            $table->decimal('tax_amount', 15, 2);
            $table->decimal('tax_rate', 5, 2);
            $table->timestamps();
        });
    }

    public function down(): void {}
};
