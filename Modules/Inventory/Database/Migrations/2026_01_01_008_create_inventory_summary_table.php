<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Inventory\Models\WarehouseLocation;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_summary', function (Blueprint $table) {
            $table->id();
            // NOTE: no Item Eloquent model exists yet, left on foreignId() until one is created.
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(WarehouseLocation::class, 'location_id')->constrained('warehouse_locations')->cascadeOnDelete();
            $table->decimal('qty_on_hand', 12, 3)->default(0);
            $table->decimal('qty_allocated', 12, 3)->default(0);
            $table->timestamp('cached_at')->nullable();
            $table->timestamps();

            $table->unique(['item_id', 'location_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_summary');
    }
};
