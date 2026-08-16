<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ledger_period_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ledger_id')->constrained('ledgers')->cascadeOnDelete();
            $table->foreignId('fiscal_period_id')->constrained('fiscal_periods')->cascadeOnDelete();
            // Only non-open statuses are stored: a period with no row here is
            // open, so opening a new year costs no rows and closing is an
            // explicit act that leaves a trace.
            $table->string('status');
            $table->timestamps();

            $table->unique(['ledger_id', 'fiscal_period_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ledger_period_statuses');
    }
};
