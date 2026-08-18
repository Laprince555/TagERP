<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Finance\Models\CashAndBanks\Banks\Bank;
use Modules\Finance\Models\GeneralLedger\Account;
use Modules\General\Models\World\Currency;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignIdFor(Bank::class)->constrained('banks')->restrictOnDelete();
            $table->string('account_number');
            $table->string('account_name');
            $table->string('account_type'); // checking, savings, fixed
            $table->foreignIdFor(Account::class, 'gl_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignIdFor(Currency::class)->constrained('currencies')->restrictOnDelete();
            $table->decimal('balance', 20, 6)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('bank_id');
            $table->index('gl_account_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_accounts');
    }
};
