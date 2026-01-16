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
        Schema::create('app_entity_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('app_entity_id')->constrained('app_entities')->cascadeOnDelete();
            $table->foreignId('app_role_id')->constrained('app_roles')->cascadeOnDelete();
            $table->boolean('can_read')->default(true);
            $table->boolean('can_write')->default(true);
            $table->boolean('can_delete')->default(false);
            $table->string('access_scope')->default('all');
            $table->timestamps();

            $table->unique(['app_entity_id', 'app_role_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_entity_permissions');
    }
};
