<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->json('description');
            $table->string('code')->unique()->index();
            $table->string('route');
            $table->string('icon')->nullable();
            $table->string('color', 32);
            $table->json('application_group')->nullable();
            $table->integer('sort_order')->default(0);
            $table->string('permission_name')->nullable();
            $table->string('permission_group')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('submodule_id');
            $table->index('submodule_id');
            $table->foreign('submodule_id', 'fk_applications_submodule_id')
                ->references('id')
                ->on('sub_modules')
                ->onDelete('restrict')
                ->onUpdate('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
