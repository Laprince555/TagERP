<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Finance\Models\CashAndBanks\Banks\Bank;
use Modules\Finance\Models\CashAndBanks\Banks\ChecksBook;
use Modules\Finance\Models\GeneralLedger\Journal;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checks', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignIdFor(ChecksBook::class)->constrained('checks_books')->restrictOnDelete();
            $table->foreignIdFor(Bank::class)->constrained('banks')->restrictOnDelete();
            $table->integer('check_number');
            $table->date('check_date');
            $table->decimal('amount', 20, 6);
            $table->string('payee_name');
            $table->text('description')->nullable();
            $table->string('status')->default('written'); // written, issued, cleared, bounced, cancelled
            $table->foreignIdFor(Journal::class)->nullable()->constrained('journals')->nullOnDelete();
            $table->timestamps();

            $table->index('checks_book_id');
            $table->index('bank_id');
            $table->index('status');
            $table->unique(['checks_book_id', 'check_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checks');
    }
};
