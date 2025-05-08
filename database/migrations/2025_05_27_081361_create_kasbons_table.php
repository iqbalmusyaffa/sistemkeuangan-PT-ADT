<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('kasbons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyek_id')->nullable()->constrained('proyeks')->onDelete('set null');
            $table->string('user_name');
            $table->string('nomor_kasbon')->unique();
            $table->decimal('amount', 15, 2);
            $table->text('description');
            $table->enum('status', ['pending', 'approved', 'disbursed', 'settled', 'rejected'])->default('pending');
            $table->date('kasbon_date');
            $table->date('due_date')->nullable();
            $table->date('approval_date')->nullable();
            $table->date('disbursement_date')->nullable();
            $table->date('settlement_date')->nullable();
            $table->string('payment_method');
            $table->string('bank_account')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('account_holder')->nullable();
            $table->text('notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('kasbon_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kasbon_id')->constrained()->onDelete('cascade');
            $table->string('file_path');
            $table->string('file_name');
            $table->string('file_type');
            $table->timestamps();
        });

        Schema::create('kasbon_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kasbon_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null'); // jika perlu user yang mencatat pembayaran
            $table->decimal('amount', 15, 2);
            $table->date('payment_date');
            $table->string('payment_method');
            $table->string('reference_number')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('kasbon_payments');
        Schema::dropIfExists('kasbon_attachments');
        Schema::dropIfExists('kasbons');
    }
};
