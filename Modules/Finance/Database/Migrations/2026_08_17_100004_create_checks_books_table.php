<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Finance\Models\CashAndBanks\Banks\Bank;
use Modules\Finance\Models\CashAndBanks\Banks\BankAccount;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checks_books', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignIdFor(Bank::class)->constrained('banks')->restrictOnDelete();
            $table->foreignIdFor(BankAccount::class)->constrained('bank_accounts')->restrictOnDelete();
            $table->integer('check_series_start');
            $table->integer('check_series_end');
            $table->integer('current_check_number');
            $table->string('status')->default('active'); // active, closed
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('bank_id');
            $table->index('bank_account_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checks_books');
    }
};
