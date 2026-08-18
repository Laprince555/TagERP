<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\HR\Models\Cycles\Cycle;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cycle_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Cycle::class, 'cycle_id')
                ->constrained('cycles')
                ->cascadeOnDelete();
            $table->morphs('subject');
            $table->string('code')->unique();
            $table->string('status')->default('in_progress');
            $table->foreignId('started_by')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cycle_transactions');
    }
};
