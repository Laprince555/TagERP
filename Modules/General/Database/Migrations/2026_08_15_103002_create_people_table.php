<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('people', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('full_name');
            $table->string('nickname')->nullable();
            $table->string('slug')->unique();
            $table->string('passport_number')->nullable();
            $table->string('national_id')->nullable();
            $table->foreignId('city_id')->nullable()
                ->constrained('cities')
                ->onDelete('restrict')
                ->onUpdate('cascade');
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('bank_account_1')->nullable();
            $table->string('iban_1')->nullable();
            $table->string('bank_account_2')->nullable();
            $table->string('iban_2')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('gender', 16)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('people');
    }
};
