<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Inventory\Models\WarehouseLocation;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('allocations', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('slug')->unique();
            // NOTE: no Item Eloquent model exists yet, left on foreignId() until one is created.
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(WarehouseLocation::class, 'location_id')->constrained('warehouse_locations')->cascadeOnDelete();
            $table->decimal('allocated_qty', 12, 3);
            $table->string('reference_order_type');
            $table->string('reference_order_id');
            $table->enum('status', ['pending', 'fulfilled', 'cancelled'])->default('pending');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['item_id', 'location_id']);
            $table->index(['reference_order_type', 'reference_order_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('allocations');
    }
};
