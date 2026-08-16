<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('slug')->unique();
            $table->foreignId('from_currency_id')->constrained('currencies')->restrictOnDelete();
            $table->foreignId('to_currency_id')->constrained('currencies')->restrictOnDelete();
            $table->date('rate_date');
            // Wide scale on purpose: weak currencies quoted against strong ones
            // need the precision, and rounding here would compound into every
            // converted journal line.
            $table->decimal('rate', 24, 10);
            // Transaction rates, period-close rates and period-average rates all
            // coexist for the same day and are used by different processes.
            $table->string('rate_type');
            $table->timestamps();

            $table->unique(['from_currency_id', 'to_currency_id', 'rate_date', 'rate_type'], 'exchange_rates_pair_date_type_unique');
            $table->index(['from_currency_id', 'to_currency_id', 'rate_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
    }
};
