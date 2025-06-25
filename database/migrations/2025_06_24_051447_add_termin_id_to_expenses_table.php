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
        Schema::table('expenses', function (Blueprint $table) {
                    $table->unsignedBigInteger('termin_id')->nullable()->after('invoice_id');
        $table->foreign('termin_id')->references('id')->on('termins')->onDelete('set null');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
           $table->dropForeign(['termin_id']);
        $table->dropColumn('termin_id');
        });
    }
};
