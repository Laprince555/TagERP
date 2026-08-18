<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\HR\Models\Cycles\CycleLine;
use Modules\HR\Models\OrganizationStructure\JobGrade;
use Modules\HR\Models\OrganizationStructure\JobTitle;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cycle_exceptions', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(CycleLine::class, 'cycle_line_id')
                ->constrained('cycle_lines')
                ->cascadeOnDelete();
            // Who is allowed to invoke this exception.
            $table->foreignIdFor(JobTitle::class, 'job_title_id')
                ->constrained('job_titles')
                ->cascadeOnDelete();
            $table->foreignIdFor(JobGrade::class, 'job_grade_id')
                ->nullable()
                ->constrained('job_grades')
                ->cascadeOnDelete();
            $table->string('exception_type');
            $table->json('condition')->nullable();
            $table->string('mode');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cycle_exceptions');
    }
};
