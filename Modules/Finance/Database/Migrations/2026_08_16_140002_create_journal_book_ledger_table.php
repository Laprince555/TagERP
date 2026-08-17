<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_book_ledger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_book_id')->constrained('journal_books')->cascadeOnDelete();
            $table->foreignId('ledger_id')->constrained('ledgers')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['journal_book_id', 'ledger_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_book_ledger');
    }
};
