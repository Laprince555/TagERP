<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ledgers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->foreignId('entity_id')->constrained('entities')->restrictOnDelete();
            $table->foreignId('chart_id')->constrained('charts')->restrictOnDelete();
            $table->foreignId('base_currency_id')->constrained('currencies')->restrictOnDelete();
            // A secondary ledger is fed from its primary: entries are entered
            // once and replicated, never keyed in twice.
            $table->boolean('is_primary')->default(true);
            $table->foreignId('primary_ledger_id')->nullable()->constrained('ledgers')->restrictOnDelete();
            $table->string('conversion_type')->nullable();
            $table->string('rate_type')->default('daily');
            // Converting each line independently leaves the copy a few units out
            // of balance; the difference is posted here so the generated journal
            // can still be posted at all.
            $table->foreignId('rounding_account_id')->nullable()->constrained('accounts')->restrictOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['entity_id', 'is_primary']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ledgers');
    }
};
