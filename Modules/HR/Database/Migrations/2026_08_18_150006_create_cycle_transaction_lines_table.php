<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\HR\Models\Cycles\CycleException;
use Modules\HR\Models\Cycles\CycleLine;
use Modules\HR\Models\Cycles\CycleTransaction;
use Modules\HR\Models\OrganizationStructure\JobGrade;
use Modules\HR\Models\OrganizationStructure\JobTitle;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cycle_transaction_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(CycleTransaction::class, 'cycle_transaction_id')
                ->constrained('cycle_transactions')
                ->cascadeOnDelete();
            // Reference only, not source of truth — everything the transaction
            // needs is snapshotted on this row.
            $table->foreignIdFor(CycleLine::class, 'cycle_line_id')
                ->constrained('cycle_lines')
                ->cascadeOnDelete();
            $table->unsignedInteger('sequence');
            $table->json('name');
            $table->foreignIdFor(JobTitle::class, 'job_title_id')
                ->constrained('job_titles')
                ->cascadeOnDelete();
            $table->foreignIdFor(JobGrade::class, 'job_grade_id')
                ->nullable()
                ->constrained('job_grades')
                ->cascadeOnDelete();
            $table->string('target_status_on_approve')->nullable();
            $table->string('target_status_on_reject')->nullable();
            $table->string('status')->default('pending');
            $table->foreignId('acted_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('acted_at')->nullable();
            $table->foreignIdFor(CycleException::class, 'used_exception_id')
                ->nullable()
                ->constrained('cycle_exceptions')
                ->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['cycle_transaction_id', 'sequence']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cycle_transaction_lines');
    }
};
