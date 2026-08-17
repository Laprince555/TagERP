<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('payment_disbursement_details')) {
            return;
        }

        Schema::create('payment_disbursement_details', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->unsignedBigInteger('payment_disbursement_request_id');
            $table->string('payee_type'); // 'employee', 'vendor', 'other'
            $table->unsignedBigInteger('payee_id')->nullable();
            $table->decimal('amount', 20, 6);
            $table->unsignedBigInteger('gl_account_id')->nullable();
            $table->text('description')->nullable();
            $table->string('reference')->nullable();
            $table->timestamps();

            $table->foreign('payment_disbursement_request_id')
                ->references('id')
                ->on('payment_disbursement_requests')
                ->cascadeOnDelete();

            $table->foreign('gl_account_id')
                ->references('id')
                ->on('accounts')
                ->nullOnDelete();

            $table->index('payment_disbursement_request_id');
            $table->index(['payee_type', 'payee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_disbursement_details');
    }
};
