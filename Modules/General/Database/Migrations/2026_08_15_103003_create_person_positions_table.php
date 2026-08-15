<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A Person's job/role history over time — the "Positions" SubApplication
 * under the Person show page. Each row is one stint: which Company (if
 * known), what role, and when. Replaces the flat company_id/position pair
 * originally proposed on `people` so history is tracked instead of only the
 * current snapshot.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('person_positions', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('slug');
            $table->foreignId('person_id')
                ->constrained('people')
                ->onDelete('restrict')
                ->onUpdate('cascade');
            $table->foreignId('company_id')->nullable()
                ->constrained('companies')
                ->onDelete('restrict')
                ->onUpdate('cascade');
            $table->string('position');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('is_current')->default(false);
            $table->timestamps();

            $table->unique(['person_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('person_positions');
    }
};
