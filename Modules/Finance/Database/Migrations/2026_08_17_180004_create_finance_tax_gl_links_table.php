<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Finance\Models\GeneralLedger\Account;
use Modules\Finance\Models\Tax\Tax;
use Modules\Finance\Models\Tax\TaxCategory;

return new class extends Migration
{
    public function up(): void
    {
        // GL routing rules for tax lines: dimensions are nullable, a null
        // dimension acts as the wildcard/fallback for that dimension when
        // resolving which account a tax amount posts to.
        Schema::create('finance_tax_gl_links', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignIdFor(TaxCategory::class, 'tax_category_id')->nullable()->constrained('finance_tax_categories')->cascadeOnDelete();
            $table->foreignIdFor(Tax::class)->nullable()->constrained('finance_taxes')->cascadeOnDelete();
            $table->foreignIdFor(Account::class)->constrained('accounts')->restrictOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // NOTE: known gap — both columns are nullable, and MySQL treats NULL as
            // distinct in unique indexes, so this constraint does not actually prevent
            // duplicate (tax_category_id, tax_id) combinations when either is null.
            // Left as-is; a schema redesign to close this gap is out of scope here.
            $table->unique(['tax_category_id', 'tax_id'], 'finance_tax_gl_links_dimensions_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_tax_gl_links');
    }
};
