<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\HR\Models\Cycles\Cycle;
use Modules\HR\Models\OrganizationStructure\JobGrade;
use Modules\HR\Models\OrganizationStructure\JobTitle;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cycle_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Cycle::class, 'cycle_id')
                ->constrained('cycles')
                ->cascadeOnDelete();
            $table->string('code')->unique();
            $table->unsignedInteger('sequence');
            $table->json('name');
            $table->foreignIdFor(JobTitle::class, 'job_title_id')
                ->constrained('job_titles')
                ->cascadeOnDelete();
            // null = any grade of that job_title, mirrors job_title_grade_roles.
            $table->foreignIdFor(JobGrade::class, 'job_grade_id')
                ->nullable()
                ->constrained('job_grades')
                ->cascadeOnDelete();
            $table->string('target_status_on_approve')->nullable();
            $table->string('target_status_on_reject')->nullable()->default('rejected');
            $table->timestamps();

            $table->unique(['cycle_id', 'sequence']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cycle_lines');
    }
};
