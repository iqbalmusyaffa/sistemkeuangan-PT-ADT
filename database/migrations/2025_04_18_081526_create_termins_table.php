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
        Schema::create('termins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyek_id')->constrained('proyeks')->onDelete('cascade');
            $table->string('nama_termin');
            $table->decimal('nilai_termin', 18, 2);
            $table->decimal('dp_percentage', 5, 2)->default(0);
            $table->decimal('nilai_dp', 18, 2)->default(0);
            $table->decimal('nilai_pelunasan', 18, 2)->default(0);
            $table->date('tanggal_dp')->nullable();
            $table->date('tanggal_pelunasan')->nullable();
            $table->enum('status_termin', ['Belum Dibayar', 'DP Dibayar', 'Lunas'])->default('Belum Dibayar');
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('termins');
    }
};
