<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sub_modules', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->json('description');
            $table->string('code')->unique()->index();
            $table->string('route');
            $table->string('icon')->nullable();
            $table->integer('sort_order')->default(0);
            $table->string('permission_group')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('module_id')
                ->constrained('modules')
                ->restrictOnDelete()
                ->cascadeOnUpdate()
                ->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sub_modules');
    }
};
