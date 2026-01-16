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
        Schema::create('app_entities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('app_version_id')->constrained('app_versions')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('table_name');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['app_version_id', 'slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_entities');
    }
};
