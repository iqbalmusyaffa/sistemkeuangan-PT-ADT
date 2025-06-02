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
        // Add status_approval to incomes
        Schema::table('incomes', function (Blueprint $table) {
            $table->enum('status_approval', ['pending', 'approved', 'rejected'])->default('pending')->after('status');
            // Rename bukti_pembayaran to bukti for consistency
            $table->renameColumn('bukti_pembayaran', 'bukti');
        });
        // Add status_approval to expenses
        Schema::table('expenses', function (Blueprint $table) {
            $table->enum('status_approval', ['pending', 'approved', 'rejected'])->default('pending')->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove status_approval from incomes
        Schema::table('incomes', function (Blueprint $table) {
            $table->dropColumn('status_approval');
            // Rename bukti back to bukti_pembayaran
            $table->renameColumn('bukti', 'bukti_pembayaran');
        });
        // Remove status_approval from expenses
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn('status_approval');
        });
    }
};
