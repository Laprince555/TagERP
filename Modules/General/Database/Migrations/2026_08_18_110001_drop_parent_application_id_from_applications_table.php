<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropForeign(['parent_application_id']);
            $table->dropColumn('parent_application_id');
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->foreignId('parent_application_id')->nullable()
                ->constrained('applications', indexName: 'app_parent_app_fk')
                ->restrictOnDelete();
        });
    }
};
