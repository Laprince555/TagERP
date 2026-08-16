<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fiscal_periods', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->foreignId('fiscal_year_id')->constrained('fiscal_years')->cascadeOnDelete();
            $table->unsignedTinyInteger('sequence');
            $table->date('start_date');
            $table->date('end_date');
            // The closing period that sits on the last day of the year: year-end
            // entries land here instead of distorting the final trading month.
            $table->boolean('is_adjustment')->default(false);
            $table->timestamps();

            $table->unique(['fiscal_year_id', 'sequence']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fiscal_periods');
    }
};
