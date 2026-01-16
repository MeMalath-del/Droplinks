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
        Schema::create('app_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('app_id')->constrained('apps')->cascadeOnDelete();
            $table->foreignId('app_version_id')->nullable()->constrained('app_versions')->nullOnDelete();
            $table->foreignId('app_page_id')->nullable()->constrained('app_pages')->nullOnDelete();
            $table->foreignId('app_action_id')->nullable()->constrained('app_actions')->nullOnDelete();
            $table->foreignId('app_record_id')->nullable()->constrained('app_records')->nullOnDelete();
            $table->string('event');
            $table->string('actor_role')->nullable();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip_address')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_audit_logs');
    }
};
