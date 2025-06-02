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
        Schema::create('expenses', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
        $table->foreignId('proyek_id')->constrained('proyeks')->onDelete('cascade');
        $table->foreignId('category_id')->nullable()->constrained('kategoris')->nullOnDelete();
        $table->foreignId('service_category_id')->nullable()->constrained('service_categories')->nullOnDelete();
        $table->decimal('amount', 15, 2);
        $table->text('description')->nullable();
        $table->date('transaction_date')->index();
        $table->enum('status', ['pending', 'DP Dibayar', 'Belum Dibayar', 'Lunas','approved', 'rejected'])->default('pending')->index();
        $table->foreignId('payment_method_id')->nullable()->constrained('payment_methods')->nullOnDelete(); // instead of string
        $table->decimal('prepared_fund', 15, 2)->default(0);
        $table->string('kode_transaksi')->nullable();
        $table->string('bukti')->nullable();
        $table->string('source_type')->nullable();
        $table->unsignedBigInteger('source_id')->nullable();
        $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
        $table->timestamps();

        $table->index(['source_type', 'source_id']);
        $table->index('invoice_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
        // Removed dropForeign and dropColumn for purchase_material_id because it does not exist in the up() migration
        // If you add new foreign keys/columns in the future, handle their removal here.
    }
};
