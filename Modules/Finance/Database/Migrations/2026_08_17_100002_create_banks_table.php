<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Finance\Models\CashAndBanks\Categories\BankCategory;
use Modules\Finance\Models\GeneralLedger\Account;
use Modules\HR\Models\OrganizationStructure\Entity;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banks', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->foreignIdFor(Entity::class)->constrained('entities')->restrictOnDelete();
            $table->foreignIdFor(BankCategory::class, 'category_id')->constrained('bank_categories')->restrictOnDelete();
            $table->foreignIdFor(Account::class, 'default_gl_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->string('bank_code')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('swift_code')->nullable();
            $table->string('iban')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('entity_id');
            $table->index('category_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banks');
    }
};
