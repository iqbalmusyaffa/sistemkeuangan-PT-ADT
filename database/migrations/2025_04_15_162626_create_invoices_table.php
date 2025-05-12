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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyek_id')->constrained('proyeks')->onDelete('cascade');
            $table->foreignId('payment_method_id')->nullable()->constrained('payment_methods')->onDelete('set null');
            $table->string('invoice_number')->unique();
            $table->date('invoice_date');
            $table->decimal('total_amount', 18, 2);
            $table->decimal('amount_paid', 18, 2)->default(0);
            $table->decimal('pph_non_final_amount', 18, 2)->default(0);
            $table->decimal('pph_final_amount', 18, 2)->default(0);
            $table->decimal('ppn_amount', 18, 2)->default(0);
            $table->decimal('net_profit', 18, 2)->default(0);
            $table->boolean('use_ppn')->default(false);
            $table->boolean('use_pph_non_final')->default(false);
            $table->boolean('use_pph_final')->default(false);
            $table->text('notes')->nullable();
            $table->enum('status', ['unpaid', 'partially_paid', 'paid', 'cancelled'])->default('unpaid');
            $table->timestamps();

            // Add indexes to improve query performance
            $table->index('invoice_number');
            $table->index('proyek_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
}; 
