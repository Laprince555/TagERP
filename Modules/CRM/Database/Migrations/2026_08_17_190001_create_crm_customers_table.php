<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Finance\Models\AccountsReceivable\CustomerCategory;
use Modules\General\Models\World\Companies\Company;
use Modules\General\Models\World\Currency;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_customers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('slug')->unique();
            $table->foreignIdFor(Company::class)->constrained('companies')->restrictOnDelete();
            $table->foreignIdFor(CustomerCategory::class, 'customer_category_id')->nullable()->constrained('finance_ar_customer_categories')->restrictOnDelete();
            $table->string('customer_type');
            $table->foreignIdFor(Currency::class, 'default_currency_id')->nullable()->constrained('currencies')->restrictOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_customers');
    }
};
