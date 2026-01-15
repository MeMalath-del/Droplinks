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
        Schema::create('app_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('app_entity_id')->constrained('app_entities')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('field_type');
            $table->boolean('is_nullable')->default(false);
            $table->boolean('is_unique')->default(false);
            $table->text('default_value')->nullable();
            $table->json('settings')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['app_entity_id', 'slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_fields');
    }
};
