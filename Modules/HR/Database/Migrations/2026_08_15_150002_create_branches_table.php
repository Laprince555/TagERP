<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->foreignId('entity_id')
                ->constrained('entities')
                ->onDelete('restrict')
                ->onUpdate('cascade');
            // Default access-scope hint only (main → entity_tree, otherwise →
            // branch); the model layer still enforces at most one main
            // branch per entity since MySQL has no partial unique index.
            $table->boolean('is_main')->default(false);
            $table->foreignId('city_id')->nullable()
                ->constrained('cities')
                ->onDelete('restrict')
                ->onUpdate('cascade');
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
