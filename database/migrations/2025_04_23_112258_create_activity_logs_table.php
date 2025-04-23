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
        Schema::create('activitylogs', function (Blueprint $table) { // Ganti ke 'activitylogs'
            $table->id();
            $table->string('user_name')->nullable(); // contoh: Admin
            $table->string('model_type'); // misalnya App\Models\Transaction
            $table->unsignedBigInteger('model_id');
            $table->string('action'); // created / updated / deleted
            $table->json('changes')->nullable(); // perubahan
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
