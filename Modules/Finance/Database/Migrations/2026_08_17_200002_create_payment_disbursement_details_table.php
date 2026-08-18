<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Finance\Models\CashAndBanks\PaymentDisburse\PaymentDisbursementRequest;
use Modules\Finance\Models\GeneralLedger\Account;

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
            $table->foreignIdFor(PaymentDisbursementRequest::class, 'payment_disbursement_request_id')
                ->constrained('payment_disbursement_requests', indexName: 'pdr_details_pdr_fk')
                ->cascadeOnDelete();
            $table->unsignedSmallInteger('line_number');
            $table->nullableMorphs('payee');
            $table->decimal('amount', 20, 6);
            $table->foreignIdFor(Account::class, 'gl_account_id')
                ->nullable()
                ->constrained('accounts', indexName: 'pdr_details_acct_fk')
                ->nullOnDelete();
            $table->text('description')->nullable();
            $table->string('reference')->nullable();
            $table->timestamps();

            $table->index('payment_disbursement_request_id', 'pdr_details_pdr_idx');
            $table->unique(['payment_disbursement_request_id', 'line_number'], 'pdr_details_line_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_disbursement_details');
    }
};
