<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('app_components', function (Blueprint $table) {
            $table->foreignId('app_datasource_id')
                ->nullable()
                ->constrained('app_datasources')
                ->nullOnDelete()
                ->after('app_page_id');
            $table->foreignId('app_action_id')
                ->nullable()
                ->constrained('app_actions')
                ->nullOnDelete()
                ->after('app_datasource_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('app_components', function (Blueprint $table) {
            $table->dropForeign(['app_datasource_id']);
            $table->dropForeign(['app_action_id']);
            $table->dropColumn(['app_datasource_id', 'app_action_id']);
        });
    }
};
