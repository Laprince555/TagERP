<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('collection_details')) {
            return;
        }

        Schema::create('collection_details', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->unsignedBigInteger('collection_request_id');
            $table->string('payer_type'); // 'customer', 'debtor', 'other'
            $table->unsignedBigInteger('payer_id')->nullable();
            $table->decimal('amount', 20, 6);
            $table->unsignedBigInteger('gl_account_id')->nullable();
            $table->text('description')->nullable();
            $table->string('reference')->nullable();
            $table->enum('collection_status', ['pending', 'collected', 'cancelled'])->default('pending');
            $table->timestamps();

            $table->foreign('collection_request_id')
                ->references('id')
                ->on('collection_requests')
                ->cascadeOnDelete();

            $table->foreign('gl_account_id')
                ->references('id')
                ->on('accounts')
                ->nullOnDelete();

            $table->index('collection_request_id');
            $table->index(['payer_type', 'payer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collection_details');
    }
};
