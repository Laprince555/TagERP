<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A grant unlocked from this job_grade's level upward, but only
        // within this same job title — e.g. "General Accountant" @ senior
        // gets fin-gl-jou.post; junior does not. The comparison is always
        // scoped to one job title's own grade ladder (via job_title_id),
        // so a senior in one title is never compared against a senior in
        // another.
        Schema::create('job_title_grade_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_title_id')
                ->constrained('job_titles')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->foreignId('job_grade_id')
                ->constrained('job_grades')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->foreignId('permission_id')
                ->constrained('permissions')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->timestamps();

            $table->unique(['job_title_id', 'job_grade_id', 'permission_id'], 'jtg_permissions_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_title_grade_permissions');
    }
};
