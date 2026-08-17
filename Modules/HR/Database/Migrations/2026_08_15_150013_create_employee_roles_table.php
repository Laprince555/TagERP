<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A direct grant of a Role (Spatie Role) to one specific employee —
        // an exception on top of whatever their job title already grants via
        // job_title_grade_roles, not a replacement for it. A fourth grant
        // source EmployeePermissionSynchronizer unions in, never a bypass of it.
        Schema::create('employee_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')
                ->constrained('employees')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->foreignId('role_id')
                ->constrained('roles')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->timestamps();

            $table->unique(['employee_id', 'role_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_roles');
    }
};
