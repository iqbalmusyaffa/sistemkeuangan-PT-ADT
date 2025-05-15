<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIncomesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('incomes', function (Blueprint $table) {
            $table->id();
            $table->string('kode_transaksi')->unique();
            $table->foreignId('kategori_id')->constrained('kategoris')->onDelete('restrict');
            $table->foreignId('payment_method_id')->constrained('payment_methods')->onDelete('restrict');
            $table->foreignId('proyek_id')->nullable()->constrained('proyeks')->onDelete('set null');
            $table->foreignId('termin_id')->nullable()->constrained('termins')->onDelete('set null');
            $table->enum('type', ['dp', 'pelunasan'])->nullable();
            $table->decimal('jumlah', 15, 2);
            $table->text('deskripsi')->nullable();
            $table->date('tanggal');
            $table->enum('status', ['Pending', 'Diterima', 'Ditolak'])->default('Pending');
            $table->string('bukti_pembayaran')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->foreignId('updated_by')->constrained('users')->onDelete('restrict');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incomes');
    }
}
