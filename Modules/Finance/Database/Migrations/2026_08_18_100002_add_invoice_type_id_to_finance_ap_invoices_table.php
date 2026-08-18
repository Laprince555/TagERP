<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Finance\Models\AccountsPayable\ApInvoiceType;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finance_ap_invoices', function (Blueprint $table) {
            // Null means "general" — the invoice applies to no specific
            // type and is intentionally excluded from type-based cycle
            // matching.
            $table->foreignIdFor(ApInvoiceType::class, 'invoice_type_id')->nullable()
                ->after('vendor_profile_id')
                ->constrained('finance_ap_invoice_types')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('finance_ap_invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignIdFor(ApInvoiceType::class, 'invoice_type_id');
        });
    }
};
