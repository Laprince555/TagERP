<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\HR\Models\OrganizationStructure\Branch;
use Modules\HR\Models\OrganizationStructure\Entity;

/**
 * Adds the entity/branch scoping columns every other Finance document
 * (Ledger, Bank, Safe, PaymentDisbursementRequest, CollectionRequest)
 * already carries — see .ai/rules/performance-security.md and
 * OrganizationScopeConstraint. Nullable: finance_ap_invoices ships with no
 * seeded rows yet, but the column is added nullable-first anyway so this
 * migration stays a safe additive change against any environment that
 * already has invoices on disk; new invoices are required to set it at the
 * application layer (ApInvoiceForm + ApInvoiceApprover::assertSubmittable).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finance_ap_invoices', function (Blueprint $table): void {
            $table->foreignIdFor(Entity::class)->nullable()
                ->after('vendor_profile_id')->constrained('entities')->restrictOnDelete();
            $table->foreignIdFor(Branch::class)->nullable()
                ->after('entity_id')->constrained('branches')->restrictOnDelete();

            $table->index('entity_id');
            $table->index('branch_id');
        });
    }

    public function down(): void
    {
        Schema::table('finance_ap_invoices', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('entity_id');
            $table->dropConstrainedForeignId('branch_id');
        });
    }
};
