<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Finance\Models\AccountsPayable\Deduction;
use Modules\Finance\Models\AccountsPayable\DeductionCategory;
use Modules\Finance\Models\GeneralLedger\Account;

return new class extends Migration
{
    public function up(): void
    {
        // Same nullable-dimension routing pattern as finance_tax_gl_links:
        // a null deduction_id/deduction_category_id is that dimension's
        // fallback when resolving which account a deduction posts to.
        Schema::create('finance_ap_deduction_gl_links', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignIdFor(DeductionCategory::class, 'deduction_category_id')->nullable()->constrained('finance_ap_deduction_categories')->cascadeOnDelete();
            $table->foreignIdFor(Deduction::class, 'deduction_id')->nullable()->constrained('finance_ap_deductions')->cascadeOnDelete();
            $table->foreignIdFor(Account::class)->constrained('accounts')->restrictOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // NOTE: known gap — both columns are nullable, and MySQL treats NULL as
            // distinct in unique indexes, so this constraint does not actually prevent
            // duplicate (deduction_category_id, deduction_id) combinations when either
            // is null. Left as-is; a schema redesign to close this gap is out of scope.
            $table->unique(['deduction_category_id', 'deduction_id'], 'finance_ap_deduction_gl_links_dimensions_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_ap_deduction_gl_links');
    }
};
