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
        Schema::create('app_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('app_entity_id')->constrained('app_entities')->cascadeOnDelete();
            $table->json('data');
            $table->string('workflow_name')->nullable();
            $table->string('workflow_state')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_records');
    }
};
