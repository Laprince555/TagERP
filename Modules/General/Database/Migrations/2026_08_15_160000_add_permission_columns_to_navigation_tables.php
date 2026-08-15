<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Modules and sub_modules gain the same permission_name Applications
        // already carry, so NavigationTreeService can gate all three levels
        // the same way ("view this module" is its own permission, distinct
        // from any application beneath it).
        Schema::table('modules', function (Blueprint $table) {
            $table->string('permission_name')->nullable()->after('permission_group');
        });

        Schema::table('sub_modules', function (Blueprint $table) {
            $table->string('permission_name')->nullable()->after('permission_group');
        });

        // Actions beyond the six standard ones (view/create/update/delete/
        // export/print) that only this application declares — e.g. "post"
        // on a journal entry application.
        Schema::table('applications', function (Blueprint $table) {
            $table->json('custom_actions')->nullable()->after('permission_group');
        });
    }

    public function down(): void
    {
        Schema::table('modules', function (Blueprint $table) {
            $table->dropColumn('permission_name');
        });

        Schema::table('sub_modules', function (Blueprint $table) {
            $table->dropColumn('permission_name');
        });

        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn('custom_actions');
        });
    }
};
