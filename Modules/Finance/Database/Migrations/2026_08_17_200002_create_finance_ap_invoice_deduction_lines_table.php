<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Finance\Models\AccountsPayable\ApInvoice;
use Modules\Finance\Models\AccountsPayable\Deduction;
use Modules\Finance\Models\GeneralLedger\CostCenter;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_ap_invoice_deduction_lines', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignIdFor(ApInvoice::class, 'invoice_id')->constrained('finance_ap_invoices')->cascadeOnDelete();
            $table->unsignedSmallInteger('line_number');
            $table->foreignIdFor(Deduction::class)->constrained('finance_ap_deductions')->restrictOnDelete();
            $table->foreignIdFor(CostCenter::class)->nullable()->constrained('cost_centers')->restrictOnDelete();
            $table->decimal('amount', 20, 6)->default(0);
            $table->timestamps();

            $table->unique(['invoice_id', 'line_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_ap_invoice_deduction_lines');
    }
};
