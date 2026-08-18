<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('items')) {
            Schema::create('items', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('name');
                $table->text('description')->nullable();
                // NOTE: no Uom Eloquent model exists yet, left on foreignId() until one is created.
                $table->foreignId('uom_id')->constrained('uom')->restrictOnDelete();
                $table->decimal('reorder_point', 12, 3)->default(0);
                $table->decimal('reorder_qty', 12, 3)->default(0);
                $table->decimal('last_cost_per_unit', 12, 4)->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index('code');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
