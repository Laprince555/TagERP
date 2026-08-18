<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Finance\Models\Tax\Tax;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_tax_adjustments', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignIdFor(Tax::class)->constrained('finance_taxes')->restrictOnDelete();
            $table->date('adjustment_date');
            // What triggered the adjustment: a settlement/payment to the tax
            // authority, or a correction following an inspection/audit result.
            $table->string('reason')->default('settlement');
            $table->decimal('amount', 20, 6)->default(0);
            $table->text('description')->nullable();
            $table->string('status')->default('draft');
            $table->foreignIdFor(User::class, 'created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_tax_adjustments');
    }
};
