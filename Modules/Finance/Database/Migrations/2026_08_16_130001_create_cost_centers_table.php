<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cost_centers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('number')->unique();
            $table->foreignId('parent_id')->nullable()->constrained('cost_centers')->restrictOnDelete();
            // A leaf normally accepts transactions; this turns one off without
            // giving it children, for a centre that is being retired but whose
            // history has to stay reportable.
            $table->boolean('accepts_transactions')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('parent_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cost_centers');
    }
};
