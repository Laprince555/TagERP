<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            // The accountant-facing account number ("1101"). Distinct from
            // `code`, which is the hierarchical system code.
            $table->string('number')->unique();
            // The hierarchy is global: an account sits under the same parent in
            // every chart that includes it, so the tree lives here and a chart
            // only ever selects a subset of it.
            $table->foreignId('parent_id')->nullable()->constrained('accounts')->restrictOnDelete();
            $table->foreignId('category_id')->constrained('account_categories')->restrictOnDelete();
            // Subsidiary-ledger control: when set, a journal line hitting this
            // account must carry an analysis of this type, which is what keeps
            // AP/AR aging reconciled with their GL control accounts. Enforced
            // once journals exist.
            $table->string('required_analysis_type')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('parent_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
