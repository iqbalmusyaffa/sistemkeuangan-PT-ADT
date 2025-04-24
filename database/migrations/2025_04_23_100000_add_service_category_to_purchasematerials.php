<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchasematerials', function (Blueprint $table) {
            // Make category_id nullable first
            $table->foreignId('category_id')->nullable()->change();
            $table->foreignId('service_category_id')->nullable()->constrained('service_categories')->onDelete('set null');
            $table->boolean('is_service')->default(false);
            // Make merek_id nullable since services don't need it
            $table->foreignId('merek_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('purchasematerials', function (Blueprint $table) {
            $table->dropForeign(['service_category_id']);
            $table->dropColumn(['service_category_id', 'is_service']);
            $table->foreignId('category_id')->nullable(false)->change();
            $table->foreignId('merek_id')->nullable(false)->change();
        });
    }
}; 