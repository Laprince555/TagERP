<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Finance\Models\GeneralLedger\Account;
use Modules\HR\Models\EmployeeManagement\Employee;
use Modules\HR\Models\OrganizationStructure\Entity;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('safes', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->foreignIdFor(Entity::class)->constrained('entities')->restrictOnDelete();
            $table->foreignIdFor(Employee::class)->nullable()->constrained('employees')->nullOnDelete();
            $table->string('location')->nullable();
            $table->text('description')->nullable();
            $table->foreignIdFor(Account::class, 'gl_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('entity_id');
            $table->index('employee_id');
            $table->index('gl_account_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('safes');
    }
};
