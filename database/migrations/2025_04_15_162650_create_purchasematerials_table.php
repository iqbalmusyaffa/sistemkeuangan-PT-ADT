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
        Schema::create('purchasematerials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->onDelete('cascade');
            $table->string('item');
            $table->foreignId('merek_id')->constrained('mereks')->onDelete('cascade');
            $table->string('type');
            $table->text('spesifikasi')->nullable();
            $table->foreignId('unit_id')->constrained('units')->onDelete('cascade');
            $table->foreignId('category_id')->constrained('kategoris')->onDelete('cascade');
            $table->integer('qty');
            $table->decimal('harga', 15, 2);
            $table->decimal('total_harga', 15, 2);
            $table->text('deskripsi')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchasematerials');
    }
};
