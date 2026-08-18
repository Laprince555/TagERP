<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Finance\Models\AccountsPayable\VendorProfile;
use Modules\Finance\Models\GeneralLedger\CostCenter;
use Modules\General\Models\World\Currency;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_ap_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('slug')->unique();
            $table->foreignIdFor(VendorProfile::class)->constrained('finance_ap_vendor_profiles')->restrictOnDelete();
            // Default cost center for the invoice's lines, taxes, and
            // deductions; a line-level cost center overrides it when a
            // single invoice spans more than one cost center.
            $table->foreignIdFor(CostCenter::class)->nullable()
                ->constrained('cost_centers')->restrictOnDelete();
            $table->string('invoice_number');
            $table->date('issue_date');
            $table->date('due_date')->nullable();
            $table->foreignIdFor(Currency::class)->constrained('currencies')->restrictOnDelete();
            $table->decimal('exchange_rate', 24, 10)->default(1);
            $table->string('po_reference')->nullable();
            $table->decimal('subtotal', 20, 6)->default(0);
            $table->decimal('tax_amount', 20, 6)->default(0);
            $table->decimal('total_amount', 20, 6)->default(0);
            $table->string('status')->default('draft');

            $table->timestamp('submitted_at')->nullable();
            $table->foreignIdFor(User::class, 'submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignIdFor(User::class, 'approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->foreignIdFor(User::class, 'rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->foreignIdFor(User::class, 'activated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->unique(['vendor_profile_id', 'invoice_number']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_ap_invoices');
    }
};
