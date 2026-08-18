<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\General\System\Application;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sub_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Application::class, 'application_id')
                ->constrained('applications', indexName: 'fk_sub_applications_application_id')
                ->cascadeOnDelete();
            $table->json('name');
            $table->json('description');
            $table->string('code')->unique()->index();
            $table->string('route')->nullable();
            $table->string('icon')->nullable();
            $table->string('color', 32)->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sub_applications');
    }
};
