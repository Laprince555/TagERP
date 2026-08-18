<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Finance\Models\Tax\TaxCategory;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_taxes', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('slug')->unique();
            $table->string('name');
            $table->foreignIdFor(TaxCategory::class)->constrained('finance_tax_categories')->restrictOnDelete();
            // Whether this tax adds to the document total (e.g. VAT) or is
            // withheld from it (e.g. WHT) — drives the sign used when the
            // amount is applied to an invoice.
            $table->string('direction')->default('addition');
            $table->decimal('rate', 8, 4)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_taxes');
    }
};
