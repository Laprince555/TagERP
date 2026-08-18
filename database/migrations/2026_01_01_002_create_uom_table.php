<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('uom')) {
            Schema::create('uom', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('name');
                $table->string('symbol')->nullable();
                $table->text('description')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index('code');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('uom');
    }
};
