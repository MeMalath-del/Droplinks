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
        Schema::create('app_page_role', function (Blueprint $table) {
            $table->id();
            $table->foreignId('app_page_id')->constrained('app_pages')->cascadeOnDelete();
            $table->foreignId('app_role_id')->constrained('app_roles')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['app_page_id', 'app_role_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_page_role');
    }
};
