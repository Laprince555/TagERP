<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Finance\Models\CashAndBanks\Collection\CollectionRequest;
use Modules\Finance\Models\GeneralLedger\Account;

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
            $table->foreignIdFor(CollectionRequest::class, 'collection_request_id')
                ->constrained('collection_requests', indexName: 'col_details_col_fk')
                ->cascadeOnDelete();
            $table->unsignedSmallInteger('line_number');
            $table->nullableMorphs('payer');
            $table->decimal('amount', 20, 6);
            $table->foreignIdFor(Account::class, 'gl_account_id')
                ->nullable()
                ->constrained('accounts', indexName: 'col_details_acct_fk')
                ->nullOnDelete();
            $table->text('description')->nullable();
            $table->string('reference')->nullable();
            $table->string('collection_status')->default('pending');
            $table->timestamps();

            $table->index('collection_request_id', 'col_details_col_idx');
            $table->unique(['collection_request_id', 'line_number'], 'col_details_line_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collection_details');
    }
};
