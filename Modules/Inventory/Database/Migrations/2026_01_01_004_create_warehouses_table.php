<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('slug')->unique();
            $table->string('name');
            $table->decimal('capacity_m3', 12, 3);
            $table->timestamps();
            $table->softDeletes();

            $table->unsignedBigInteger('branch_id')->nullable();
            $table->index('branch_id');
            $table->index('code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouses');
    }
};
