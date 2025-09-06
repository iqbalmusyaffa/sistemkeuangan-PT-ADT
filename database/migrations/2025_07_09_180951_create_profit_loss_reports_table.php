<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('profit_loss_reports', function (Blueprint $table) {
      $table->id();
            $table->foreignId('proyek_id')->nullable()->constrained('proyeks')->onDelete('set null');
            $table->string('period_type'); // weekly, monthly, yearly
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('total_income', 15, 2)->default(0);
            $table->decimal('total_expense', 15, 2)->default(0);
            $table->decimal('net_profit', 15, 2)->default(0);
            $table->json('income_details')->nullable();
            $table->json('expense_details')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profit_loss_reports');
    }
};
