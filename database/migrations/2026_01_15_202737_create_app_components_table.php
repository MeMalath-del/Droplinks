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
        Schema::create('app_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('app_page_id')->constrained('app_pages')->cascadeOnDelete();
            $table->string('component_type');
            $table->string('name')->nullable();
            $table->json('props')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_components');
    }
};
