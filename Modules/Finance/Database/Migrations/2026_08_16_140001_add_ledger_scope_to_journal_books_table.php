<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journal_books', function (Blueprint $table) {
            // Where documents of this kind are carried to. Routing lives on the
            // book rather than on each journal because the tax treatment of a
            // document type is decided once by whoever knows the rules, not five
            // hundred times a day by whoever is keying.
            $table->string('ledger_scope')->default('all')->after('sequence_prefix');
        });
    }

    public function down(): void
    {
        Schema::table('journal_books', function (Blueprint $table) {
            $table->dropColumn('ledger_scope');
        });
    }
};
