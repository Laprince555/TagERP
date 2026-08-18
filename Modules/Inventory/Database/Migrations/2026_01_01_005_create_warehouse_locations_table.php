<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Inventory\Models\Warehouse;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouse_locations', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignIdFor(Warehouse::class)->constrained()->cascadeOnDelete();
            $table->tinyInteger('level'); // 1=Aisle, 2=Shelf, 3=Bin
            $table->string('aisle')->nullable();
            $table->string('shelf')->nullable();
            $table->string('bin')->nullable();
            $table->decimal('capacity_units', 12, 3);
            // NOTE: no Uom Eloquent model exists yet, left on foreignId() until one is created.
            $table->foreignId('uom_id')->constrained('uom')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['warehouse_id', 'aisle', 'shelf', 'bin']);
            $table->index(['warehouse_id', 'level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_locations');
    }
};
