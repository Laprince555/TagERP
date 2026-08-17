<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A journal number is unique within its ledger, not across the system.
     *
     * Each ledger runs its own sequence per book: the primary numbers its
     * documents, and each secondary numbers the copies it receives. A single
     * global sequence would leave the primary's numbering full of gaps where
     * the copies took slots, which is exactly what an auditor asks about.
     */
    public function up(): void
    {
        Schema::table('journals', function (Blueprint $table) {
            $table->dropUnique(['number']);
            $table->unique(['ledger_id', 'number']);
        });
    }

    public function down(): void
    {
        Schema::table('journals', function (Blueprint $table) {
            $table->dropUnique(['ledger_id', 'number']);
            $table->unique(['number']);
        });
    }
};
