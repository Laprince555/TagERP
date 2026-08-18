<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Finance\Models\CashAndBanks\Banks\Bank;
use Modules\Finance\Models\CashAndBanks\Banks\BankAccount;
use Modules\Finance\Models\GeneralLedger\Account;
use Modules\Finance\Models\GeneralLedger\Journal;
use Modules\General\Models\World\Currency;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_expenses', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('slug')->unique();
            $table->string('number')->nullable()->unique();
            $table->foreignIdFor(Bank::class)->constrained('banks')->restrictOnDelete();
            $table->foreignIdFor(BankAccount::class)->nullable()->constrained('bank_accounts')->nullOnDelete();
            $table->date('expense_date');
            $table->decimal('amount', 20, 6);
            $table->foreignIdFor(Currency::class)->constrained('currencies')->restrictOnDelete();
            $table->string('expense_type'); // service_fee, interest, commission, etc.
            $table->text('description')->nullable();
            $table->string('invoice_reference')->nullable();
            $table->foreignIdFor(Account::class, 'gl_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->string('status')->default('draft'); // draft, posted
            $table->timestamp('posted_at')->nullable();
            $table->foreignIdFor(Journal::class)->nullable()->constrained('journals')->nullOnDelete();
            $table->string('reconciliation_status')->default('unreconciled'); // unreconciled, reconciled
            $table->timestamps();

            $table->index('bank_id');
            $table->index('status');
            $table->index('journal_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_expenses');
    }
};
