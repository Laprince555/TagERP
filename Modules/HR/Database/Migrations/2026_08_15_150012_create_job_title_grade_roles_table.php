<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Named Spatie roles (e.g. "finance_manager") attached to a job
        // title, optionally narrowed to one grade and above (null grade =
        // every grade of that title).
        Schema::create('job_title_grade_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_title_id')
                ->constrained('job_titles')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->foreignId('job_grade_id')->nullable()
                ->constrained('job_grades')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->foreignId('role_id')
                ->constrained('roles')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->timestamps();

            $table->unique(['job_title_id', 'job_grade_id', 'role_id'], 'jtg_roles_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_title_grade_roles');
    }
};
