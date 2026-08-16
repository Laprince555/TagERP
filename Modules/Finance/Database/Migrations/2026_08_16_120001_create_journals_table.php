<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journals', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('slug')->unique();
            // The document number an accountant quotes: "REC-2026-0001".
            $table->string('number')->unique()->nullable();
            $table->foreignId('ledger_id')->constrained('ledgers')->restrictOnDelete();
            $table->foreignId('journal_book_id')->constrained('journal_books')->restrictOnDelete();
            $table->foreignId('fiscal_period_id')->constrained('fiscal_periods')->restrictOnDelete();
            $table->date('journal_date');
            $table->text('description')->nullable();
            $table->string('status')->default('draft');
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            // Totals are stored rather than aggregated per row: a journal list
            // cannot sum its lines without one query per row, and a posted
            // journal's totals can never change anyway.
            $table->decimal('total_debit', 20, 6)->default(0);
            $table->decimal('total_credit', 20, 6)->default(0);
            // The document this journal came from, in whichever module raised it.
            $table->nullableMorphs('source');
            $table->string('source_reference')->nullable();
            // Set on the copies a secondary ledger receives from its primary.
            $table->foreignId('source_journal_id')->nullable()->constrained('journals')->cascadeOnDelete();
            $table->foreignId('reverses_journal_id')->nullable()->constrained('journals')->restrictOnDelete();
            $table->timestamps();

            $table->index(['ledger_id', 'fiscal_period_id', 'status']);
            $table->index(['ledger_id', 'journal_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journals');
    }
};
