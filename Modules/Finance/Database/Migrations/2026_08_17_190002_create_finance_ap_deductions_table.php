<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Finance\Models\AccountsPayable\DeductionCategory;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_ap_deductions', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('slug')->unique();
            $table->string('name');
            $table->foreignIdFor(DeductionCategory::class, 'deduction_category_id')->constrained('finance_ap_deduction_categories')->restrictOnDelete();
            // 'fixed' takes `value` as a flat amount, 'percentage' applies it
            // against the invoice's taxable base.
            $table->string('calculation_type')->default('fixed');
            $table->decimal('value', 20, 6)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_ap_deductions');
    }
};
