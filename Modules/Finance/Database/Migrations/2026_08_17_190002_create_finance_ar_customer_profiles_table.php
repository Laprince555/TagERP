<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\CRM\Models\Customers\Customer;
use Modules\Finance\Models\GeneralLedger\Account;
use Modules\General\Models\World\Currency;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_ar_customer_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('slug')->unique();
            $table->foreignIdFor(Customer::class)->constrained('crm_customers')->restrictOnDelete();
            $table->string('financial_role');
            $table->foreignIdFor(Currency::class)->constrained('currencies')->restrictOnDelete();
            $table->foreignIdFor(Account::class, 'ar_account_id')->constrained('accounts')->restrictOnDelete();
            $table->unsignedSmallInteger('credit_terms_days')->default(0);
            $table->decimal('credit_limit', 15, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('customer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_ar_customer_profiles');
    }
};
