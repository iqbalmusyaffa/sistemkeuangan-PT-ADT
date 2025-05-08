<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyek_id')->nullable()->constrained('proyeks')->onDelete('set null');
            $table->string('name');
            $table->string('description')->nullable();
            $table->decimal('total_amount', 15, 2);
            $table->decimal('used_amount', 15, 2)->default(0);
            $table->decimal('remaining_amount', 15, 2);
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status')->default('active'); // active, completed, cancelled
            $table->json('categories')->nullable(); // JSON structure for budget categories
            $table->timestamps();
        });

        Schema::create('budget_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('description')->nullable();
            $table->decimal('allocated_amount', 15, 2);
            $table->decimal('used_amount', 15, 2)->default(0);
            $table->decimal('remaining_amount', 15, 2);
            $table->timestamps();
        });

        Schema::create('budget_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_id')->constrained()->onDelete('cascade');
            $table->foreignId('budget_category_id')->constrained()->onDelete('cascade');
            $table->string('transaction_type'); // income, expense
            $table->decimal('amount', 15, 2);
            $table->string('description');
            $table->date('transaction_date');
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('budget_transactions');
        Schema::dropIfExists('budget_categories');
        Schema::dropIfExists('budgets');
    }
}; 