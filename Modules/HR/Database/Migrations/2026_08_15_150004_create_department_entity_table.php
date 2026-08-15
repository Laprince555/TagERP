<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Which entities (and optionally which single branch of that entity)
        // have a given department active. branch_id null = the whole entity;
        // set = that one branch only — the same group/entity/branch
        // distinction as before, now expressed per attachment instead of
        // baked into the department row itself.
        Schema::create('department_entity', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')
                ->constrained('departments')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->foreignId('entity_id')
                ->constrained('entities')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->foreignId('branch_id')->nullable()
                ->constrained('branches')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->timestamps();

            $table->unique(['department_id', 'entity_id', 'branch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('department_entity');
    }
};
