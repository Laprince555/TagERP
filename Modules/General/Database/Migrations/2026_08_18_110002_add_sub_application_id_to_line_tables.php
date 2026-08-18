<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\General\System\SubApplication;

return new class extends Migration
{
    private array $tables = [
        'journal_lines',
        'finance_ap_invoice_lines',
        'finance_ap_invoice_tax_lines',
        'finance_ap_invoice_deduction_lines',
        'payment_disbursement_details',
        'collection_details',
        'warehouse_locations',
        'cycle_count_lines',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                $blueprint->foreignIdFor(SubApplication::class, 'sub_application_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('sub_applications', indexName: "fk_{$table}_sub_application_id")
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                $blueprint->dropForeign("fk_{$table}_sub_application_id");
                $blueprint->dropColumn('sub_application_id');
            });
        }
    }
};
