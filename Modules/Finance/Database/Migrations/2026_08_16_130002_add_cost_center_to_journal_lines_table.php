<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journal_lines', function (Blueprint $table) {
            // An explicit column rather than a generic dimension row: this is
            // what every cost report groups by, and it stays indexable.
            $table->foreignId('cost_center_id')->nullable()->after('account_id')
                ->constrained('cost_centers')->restrictOnDelete();

            $table->index(['cost_center_id', 'journal_id']);
        });
    }

    public function down(): void
    {
        Schema::table('journal_lines', function (Blueprint $table) {
            $table->dropForeign(['cost_center_id']);
            $table->dropIndex(['cost_center_id', 'journal_id']);
            $table->dropColumn('cost_center_id');
        });
    }
};
