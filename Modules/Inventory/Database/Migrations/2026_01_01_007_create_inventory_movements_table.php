<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Inventory\Models\BatchLot;
use Modules\Inventory\Models\WarehouseLocation;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('slug')->unique();
            // NOTE: no Item Eloquent model exists yet, left on foreignId() until one is created.
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(WarehouseLocation::class, 'location_id')->constrained('warehouse_locations')->cascadeOnDelete();
            $table->enum('movement_type', ['IN', 'OUT', 'TRANSFER', 'ADJUSTMENT', 'ALLOCATION', 'DEALLOCATION']);
            $table->decimal('quantity', 12, 3);
            // NOTE: no Uom Eloquent model exists yet, left on foreignId() until one is created.
            $table->foreignId('uom_id')->constrained('uom')->restrictOnDelete();
            $table->decimal('cost_per_unit', 12, 4)->nullable();
            $table->string('reference_doc_type')->nullable();
            $table->string('reference_doc_id')->nullable();
            $table->foreignIdFor(BatchLot::class, 'batch_lot_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignIdFor(User::class, 'created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Indexes for fast queries
            $table->index('movement_type');
            $table->index(['item_id', 'location_id']);
            $table->index(['reference_doc_type', 'reference_doc_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
