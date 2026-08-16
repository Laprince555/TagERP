<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_group_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_group_id')->constrained('account_groups')->cascadeOnDelete();
            // Assigned to a person or to a job title. A job title with nobody in
            // it grants nothing and harms nothing — it is a standing rule waiting
            // for whoever fills the post.
            $table->morphs('assignable');
            $table->timestamps();

            $table->unique(['account_group_id', 'assignable_type', 'assignable_id'], 'account_group_assignable_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_group_assignments');
    }
};
