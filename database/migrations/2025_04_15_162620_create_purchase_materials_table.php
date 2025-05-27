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
        Schema::create('purchase_materials', function (Blueprint $table) {
            $table->id();
           $table->foreignId('invoice_id')->nullable()->constrained('invoices')->onDelete('cascade');
            $table->foreignId('proyek_id')->constrained('proyeks')->onDelete('cascade');
            $table->string('item');
            $table->foreignId('merek_id')->nullable()->constrained('mereks')->nullOnDelete();
            $table->string('type');
            $table->text('spesifikasi')->nullable();
            $table->foreignId('unit_id')->constrained('units')->onDelete('cascade');
            $table->foreignId('category_id')->nullable()->constrained('kategoris')->nullOnDelete();
            $table->integer('qty');
            $table->decimal('harga', 15, 2);
            $table->decimal('total_harga', 18, 2);
            $table->text('deskripsi')->nullable();
            $table->boolean('is_service')->default(false);
            $table->foreignId('service_category_id')->nullable()->constrained('service_categories')->nullOnDelete();
           $table->unsignedBigInteger('expense_id')->nullable();
            $table->foreignId('termin_id')->nullable()->constrained('termins')->onDelete('set null');
            $table->timestamps();

            $table->index('invoice_id');
            $table->index('proyek_id');
            $table->index('merek_id');
            $table->index('unit_id');
            $table->index('category_id');
            $table->index('service_category_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_materials');
    }
};
