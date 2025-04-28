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
        Schema::table('purchase_materials', function (Blueprint $table) {
            $table->foreignId('termin_id')->nullable()->constrained('termins')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_materials', function (Blueprint $table) {
            $table->dropForeign(['termin_id']);
            $table->dropColumn('termin_id');
        });
    }
};
