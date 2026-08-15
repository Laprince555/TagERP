<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Grants that apply to anyone holding this job title, regardless of
        // grade — the baseline every grade of "General Accountant" gets.
        Schema::create('job_title_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_title_id')
                ->constrained('job_titles')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->foreignId('permission_id')
                ->constrained('permissions')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->timestamps();

            $table->unique(['job_title_id', 'permission_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_title_permissions');
    }
};
