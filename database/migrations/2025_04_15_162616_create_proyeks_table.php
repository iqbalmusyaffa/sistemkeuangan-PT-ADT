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
        Schema::create('proyeks', function (Blueprint $table) {
            $table->id();
            $table->string('nama_customer'); // langsung string customer
            $table->string('nama_proyek');
            $table->string('nama_perusahaan'); // langsung string perusahaan
            $table->string('alamat');
            $table->string('no_telp');
            $table->string('email')->nullable();
            $table->string('lokasi')->nullable();
            $table->decimal('anggaran_kontrak', 18, 2);
            $table->decimal('budget_adjusted', 15, 2)->nullable();
            $table->date('tanggal_mulai')->nullable();
            $table->date('tanggal_selesai')->nullable();
            $table->enum('status_project', ['Berjalan', 'Selesai', 'Batal'])->default('Berjalan');
            $table->text('deskripsi')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proyeks');
    }
};
