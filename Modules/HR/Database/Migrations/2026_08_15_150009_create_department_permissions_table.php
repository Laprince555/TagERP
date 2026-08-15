<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Direct grants only — not inherited down the department tree by
        // design, so "Procurement" (a child of "Finance") does not silently
        // gain Finance's module access just because it sits beneath it.
        Schema::create('department_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')
                ->constrained('departments')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->foreignId('permission_id')
                ->constrained('permissions')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->timestamps();

            $table->unique(['department_id', 'permission_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('department_permissions');
    }
};
