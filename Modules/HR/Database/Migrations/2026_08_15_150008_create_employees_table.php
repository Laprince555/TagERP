<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('slug')->unique();
            $table->string('employee_number')->unique();
            $table->foreignId('person_id')->unique()
                ->constrained('people')
                ->onDelete('restrict')
                ->onUpdate('cascade');
            // Nullable: not every employee logs into the system (e.g. factory workers).
            $table->foreignId('user_id')->nullable()->unique()
                ->constrained('users')
                ->onDelete('restrict')
                ->onUpdate('cascade');
            $table->foreignId('entity_id')
                ->constrained('entities')
                ->onDelete('restrict')
                ->onUpdate('cascade');
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->onDelete('restrict')
                ->onUpdate('cascade');
            $table->foreignId('department_id')
                ->constrained('departments')
                ->onDelete('restrict')
                ->onUpdate('cascade');
            $table->foreignId('job_title_id')
                ->constrained('job_titles')
                ->onDelete('restrict')
                ->onUpdate('cascade');
            $table->foreignId('job_grade_id')
                ->constrained('job_grades')
                ->onDelete('restrict')
                ->onUpdate('cascade');
            // Which companies (this entity + descendants, or just this entity/branch)
            // the employee's data access spans.
            $table->enum('entity_scope', ['own', 'branch', 'entity', 'entity_tree']);
            // Which slice of the department tree the employee's data access spans,
            // independent of entity_scope — the visible set is the intersection of both.
            $table->enum('department_scope', ['own', 'department', 'department_tree', 'all']);
            $table->decimal('gross_salary', 12, 2)->nullable();
            $table->enum('status', ['active', 'suspended', 'terminated'])->default('active');
            $table->date('hire_date');
            $table->date('termination_date')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // The hot path: "which active employee record belongs to this logged-in user".
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
