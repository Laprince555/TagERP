<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_lines', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('journal_id')->constrained('journals')->cascadeOnDelete();
            $table->unsignedSmallInteger('line_number');
            $table->foreignId('account_id')->constrained('accounts')->restrictOnDelete();
            $table->text('description')->nullable();

            // Amounts as entered, in the currency of the transaction.
            $table->foreignId('currency_id')->constrained('currencies')->restrictOnDelete();
            $table->decimal('exchange_rate', 24, 10)->default(1);
            $table->decimal('debit', 20, 6)->default(0);
            $table->decimal('credit', 20, 6)->default(0);

            // The same amounts in the ledger's base currency, frozen at posting
            // time. Recomputing them on read would silently rewrite history
            // every time somebody corrected an old exchange rate.
            $table->decimal('base_debit', 20, 6)->default(0);
            $table->decimal('base_credit', 20, 6)->default(0);

            // Who or what this line is about — the supplier, customer, employee
            // or asset behind the amount. The morph navigates; the snapshot
            // columns are what reports read and what keeps a posted line
            // showing the name that was true when it was posted.
            $table->nullableMorphs('analysis');
            $table->string('analysis_code')->nullable();
            $table->string('analysis_name')->nullable();

            $table->timestamps();

            $table->unique(['journal_id', 'line_number']);
            $table->index(['account_id', 'journal_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_lines');
    }
};
