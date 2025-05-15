<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTerminsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('termins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyek_id')->constrained('proyeks')->onDelete('cascade');
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->onDelete('set null');
            $table->string('nama_termin');
            $table->decimal('nilai_termin', 15, 2);
            $table->decimal('dp_percentage', 5, 2);
            $table->decimal('nilai_dp', 15, 2);
            $table->decimal('nilai_pelunasan', 15, 2);
            $table->date('tanggal_dp')->nullable();
            $table->date('tanggal_pelunasan')->nullable();
            $table->enum('status_termin', ['Belum Dibayar', 'DP Dibayar', 'Lunas'])->default('Belum Dibayar');
            $table->text('keterangan')->nullable();
            $table->string('bukti_pembayaran')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('termins');
    }
}
