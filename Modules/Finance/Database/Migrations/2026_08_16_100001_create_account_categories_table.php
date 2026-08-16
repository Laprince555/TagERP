<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            // The five root natures are a closed set (AccountNature), so they
            // are stored as an enum value rather than a user-editable table:
            // every report keys off them, and a sixth nature would have no
            // defined normal balance or statement placement.
            $table->string('nature');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('nature');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_categories');
    }
};
