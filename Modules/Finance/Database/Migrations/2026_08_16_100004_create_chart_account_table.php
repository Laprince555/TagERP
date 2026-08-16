<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chart_account', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chart_id')->constrained('charts')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['chart_id', 'account_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chart_account');
    }
};
