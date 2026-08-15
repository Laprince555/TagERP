<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A department is a group-wide catalog definition ("Finance",
        // "Payroll") shared by every entity that uses it — entity-agnostic
        // by design. Which entities/branches actually have it active lives
        // in department_entity, not here.
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->foreignId('parent_department_id')->nullable()
                ->constrained('departments')
                ->onDelete('restrict')
                ->onUpdate('cascade');
            $table->string('path')->nullable()->index();
            $table->unsignedSmallInteger('depth')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};
