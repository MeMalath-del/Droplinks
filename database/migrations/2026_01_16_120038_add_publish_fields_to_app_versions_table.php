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
        Schema::table('app_versions', function (Blueprint $table) {
            $table->timestamp('published_at')->nullable()->after('notes');
            $table->foreignId('copied_from_version_id')
                ->nullable()
                ->constrained('app_versions')
                ->nullOnDelete()
                ->after('app_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('app_versions', function (Blueprint $table) {
            $table->dropForeign(['copied_from_version_id']);
            $table->dropColumn(['published_at', 'copied_from_version_id']);
        });
    }
};
