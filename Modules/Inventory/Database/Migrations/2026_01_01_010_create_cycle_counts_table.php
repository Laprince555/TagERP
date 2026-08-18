<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Inventory\Models\CycleCount;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Inventory\Models\Warehouse;
use Modules\Inventory\Models\WarehouseLocation;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cycle_counts', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('slug')->unique();
            $table->foreignIdFor(Warehouse::class)->constrained()->cascadeOnDelete();
            $table->enum('status', ['scheduled', 'in_progress', 'completed', 'variance_applied'])->default('scheduled');
            $table->date('count_date');
            $table->foreignIdFor(User::class, 'counted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignIdFor(User::class, 'approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('count_date');
        });

        Schema::create('cycle_count_lines', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignIdFor(CycleCount::class)->constrained()->cascadeOnDelete();
            // NOTE: no Item Eloquent model exists yet, left on foreignId() until one is created.
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(WarehouseLocation::class, 'location_id')->constrained('warehouse_locations')->cascadeOnDelete();
            $table->decimal('system_qty', 12, 3);
            $table->decimal('physical_qty', 12, 3)->nullable();
            $table->foreignIdFor(InventoryMovement::class, 'adjustment_movement_id')->nullable()->constrained('inventory_movements')->nullOnDelete();
            $table->timestamps();

            $table->index(['item_id', 'location_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cycle_count_lines');
        Schema::dropIfExists('cycle_counts');
    }
};
