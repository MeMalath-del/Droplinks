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
        Schema::create('app_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('app_version_id')->constrained('app_versions')->cascadeOnDelete();
            $table->foreignId('app_entity_id')->nullable()->constrained('app_entities')->nullOnDelete();
            $table->foreignId('app_record_id')->nullable()->constrained('app_records')->nullOnDelete();
            $table->string('disk')->default('local');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_files');
    }
};
