<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Inventory\Models\Warehouse;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('batch_lots', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('slug')->unique();
            $table->string('batch_number');
            // NOTE: no Item Eloquent model exists yet, left on foreignId() until one is created.
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Warehouse::class)->constrained()->cascadeOnDelete();
            $table->date('expiry_date')->nullable();
            $table->decimal('cost_per_unit', 12, 4);
            $table->decimal('qty_available', 12, 3)->default(0);
            $table->timestamps();

            $table->unique(['batch_number', 'item_id', 'warehouse_id']);
            $table->index('expiry_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batch_lots');
    }
};
