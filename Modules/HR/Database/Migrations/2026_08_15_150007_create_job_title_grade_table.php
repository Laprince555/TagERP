<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Which grades are valid for which job title — e.g. "General
        // Accountant" may only ever be junior/senior, never "manager".
        Schema::create('job_title_grade', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_title_id')
                ->constrained('job_titles')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->foreignId('job_grade_id')
                ->constrained('job_grades')
                ->onDelete('restrict')
                ->onUpdate('cascade');
            $table->timestamps();

            $table->unique(['job_title_id', 'job_grade_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_title_grade');
    }
};
