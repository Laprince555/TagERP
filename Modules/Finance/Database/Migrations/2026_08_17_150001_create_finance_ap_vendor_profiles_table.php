<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Finance\Models\GeneralLedger\Account;
use Modules\General\Models\World\Currency;
use Modules\Procurement\Models\Vendors\Vendor;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_ap_vendor_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('slug')->unique();
            $table->foreignIdFor(Vendor::class)->constrained('procurement_vendors')->restrictOnDelete();
            $table->string('financial_role');
            $table->foreignIdFor(Currency::class)->constrained('currencies')->restrictOnDelete();
            $table->foreignIdFor(Account::class, 'ap_account_id')->constrained('accounts')->restrictOnDelete();
            $table->unsignedSmallInteger('payment_terms_days')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('vendor_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_ap_vendor_profiles');
    }
};
