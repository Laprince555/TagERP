<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entities', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->foreignId('company_id')->unique()
                ->constrained('companies')
                ->onDelete('restrict')
                ->onUpdate('cascade');
            $table->foreignId('parent_entity_id')->nullable()
                ->constrained('entities')
                ->onDelete('restrict')
                ->onUpdate('cascade');
            // Materialized path of ancestor ids, e.g. "/1/4/9/" — lets the
            // organization scope resolver find descendants with a single
            // indexed LIKE instead of a recursive query on every request.
            $table->string('path')->nullable()->index();
            $table->unsignedSmallInteger('depth')->default(0);
            $table->boolean('is_holding')->default(false);
            $table->foreignId('currency_id')->nullable()
                ->constrained('currencies')
                ->onDelete('restrict')
                ->onUpdate('cascade');
            $table->unsignedTinyInteger('fiscal_year_start_month')->nullable();
            $table->unsignedTinyInteger('fiscal_year_start_day')->nullable();
            $table->string('legal_form')->nullable();
            $table->decimal('ownership_percentage', 5, 2)->nullable();
            $table->string('tax_authority')->nullable();
            $table->json('board_members')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entities');
    }
};
