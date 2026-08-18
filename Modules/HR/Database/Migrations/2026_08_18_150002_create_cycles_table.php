<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\HR\Models\Cycles\CycleType;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cycles', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(CycleType::class, 'cycle_type_id')
                ->constrained('cycle_types')
                ->cascadeOnDelete();
            $table->string('code')->unique();
            $table->string('slug')->unique();
            $table->json('name');
            $table->text('description')->nullable();
            // FQCN of the Eloquent model this cycle governs.
            $table->string('subject_model');
            // Matches the subject's own classification value; null = applies to
            // any document of that subject_model.
            $table->string('document_type_value')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['subject_model', 'document_type_value'], 'cycles_subject_doctype_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cycles');
    }
};
