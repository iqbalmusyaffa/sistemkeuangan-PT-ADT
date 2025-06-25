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
           $table->unsignedBigInteger('expense_id')->nullable();

            $table->string('nama_termin');
             $table->float('target_progress')->default(0); // Target progress untuk termin ini
            $table->enum('jenis_termin', ['DP', 'Pelunasan', 'Termin Bertahap'])->default('Termin Bertahap')->index();
            $table->integer('termin_ke')->nullable(); // Untuk urutan termin bertahap

            $table->decimal('nilai_termin', 15, 2);
            $table->decimal('persentase_dp', 5, 2)->nullable(); // Optional kalau bukan DP
            $table->decimal('nilai_dp', 15, 2)->nullable();
            $table->decimal('nilai_pelunasan', 15, 2)->nullable();

            $table->date('tanggal_dp')->nullable();
            $table->date('tanggal_pelunasan')->nullable();
            $table->date('tanggal_dp_dibayar')->nullable();
            $table->date('tanggal_pelunasan_dibayar')->nullable();

            $table->enum('status_termin', ['Belum Dibayar', 'Siap Bayar', 'DP Dibayar', 'Lunas'])->default('Belum Dibayar')->index();

            // Tambahan kolom approval
            $table->string('status_approval')->default('Pending');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->string('bukti_pembayaran_url')->nullable();

            $table->text('keterangan')->nullable();
            $table->string('bukti_pembayaran')->nullable();

            $table->foreignId('dibayar_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            // Foreign key tambahan
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');

            // Index tambahan
            $table->index('proyek_id');
            $table->index('invoice_id');
            $table->index('dibayar_oleh');
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
