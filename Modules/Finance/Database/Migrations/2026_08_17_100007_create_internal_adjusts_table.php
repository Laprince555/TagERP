<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Finance\Models\CashAndBanks\Banks\Bank;
use Modules\Finance\Models\CashAndBanks\Safes\Safe;
use Modules\Finance\Models\GeneralLedger\Journal;
use Modules\General\Models\World\Currency;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('internal_adjusts', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('slug')->unique();
            $table->string('number')->nullable()->unique();
            $table->foreignIdFor(Bank::class, 'from_bank_id')->nullable()->constrained('banks')->nullOnDelete();
            $table->foreignIdFor(Safe::class, 'from_safe_id')->nullable()->constrained('safes')->nullOnDelete();
            $table->foreignIdFor(Bank::class, 'to_bank_id')->nullable()->constrained('banks')->nullOnDelete();
            $table->foreignIdFor(Safe::class, 'to_safe_id')->nullable()->constrained('safes')->nullOnDelete();
            $table->date('adjustment_date');
            $table->decimal('amount', 20, 6);
            $table->foreignIdFor(Currency::class)->constrained('currencies')->restrictOnDelete();
            $table->text('description')->nullable();
            $table->string('reference')->nullable();
            $table->string('status')->default('draft'); // draft, posted
            $table->timestamp('posted_at')->nullable();
            $table->foreignIdFor(Journal::class)->nullable()->constrained('journals')->nullOnDelete();
            $table->timestamps();

            $table->index('status');
            $table->index('journal_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('internal_adjusts');
    }
};
