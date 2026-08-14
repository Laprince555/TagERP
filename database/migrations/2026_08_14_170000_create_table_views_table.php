<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('table_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('table_key');
            $table->string('name');
            $table->json('configuration');
            $table->unsignedInteger('schema_version')->default(1);
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'table_key', 'name']);
            $table->index(['user_id', 'table_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('table_views');
    }
};
