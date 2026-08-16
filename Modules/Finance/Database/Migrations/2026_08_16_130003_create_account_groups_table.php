<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_groups', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            // Two uses, one shape: a template is a reusable set of accounts to
            // build charts from, an access group is what a person is allowed to
            // see. Separate tables would have been the same columns twice.
            $table->string('purpose')->default('access');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('purpose');
        });

        Schema::create('account_group_account', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_group_id')->constrained('account_groups')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['account_group_id', 'account_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_group_account');
        Schema::dropIfExists('account_groups');
    }
};
