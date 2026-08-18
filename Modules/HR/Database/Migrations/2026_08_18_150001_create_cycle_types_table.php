<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cycle_types', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            // Code of the App/SubModule whose documents this cycle type governs
            // (e.g. 'fin-ap-inv'), not an FK — the target Application is defined
            // in another module and may not exist yet.
            $table->string('application_code');
            $table->json('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cycle_types');
    }
};
