<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Finance\Models\CashAndBanks\Banks\Bank;
use Modules\Finance\Models\CashAndBanks\Safes\Safe;
use Modules\Finance\Models\GeneralLedger\Journal;
use Modules\General\Models\World\Currency;
use Modules\HR\Models\OrganizationStructure\Entity;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_disbursement_requests', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('slug')->unique();
            $table->string('number')->unique();
            $table->foreignIdFor(Entity::class)->constrained('entities')->restrictOnDelete();
            $table->date('payment_date');
            $table->decimal('amount', 20, 6);
            $table->foreignIdFor(Currency::class)->constrained('currencies')->restrictOnDelete();
            $table->text('description')->nullable();
            $table->enum('status', ['draft', 'pending', 'approved', 'posted', 'cancelled'])->default('draft');
            $table->timestamp('posted_at')->nullable();
            $table->foreignIdFor(Journal::class)->nullable()->constrained('journals')->nullOnDelete();
            $table->enum('payment_method', ['bank_transfer', 'check', 'cash'])->default('bank_transfer');
            $table->foreignIdFor(Bank::class, 'from_bank_id')->nullable()->constrained('banks')->nullOnDelete();
            $table->foreignIdFor(Safe::class, 'from_safe_id')->nullable()->constrained('safes')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('entity_id');
            $table->index('posted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_disbursement_requests');
    }
};
