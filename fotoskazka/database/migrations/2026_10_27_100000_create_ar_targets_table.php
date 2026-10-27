<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ar_targets', function (Blueprint $table) {
            $table->id();
            $table->string('token', 32)->unique();
            $table->string('title');
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('photo_id')->nullable()->constrained()->nullOnDelete();
            $table->string('disk', 50);
            $table->string('marker_path', 1000);
            $table->unsignedInteger('marker_width');
            $table->unsignedInteger('marker_height');
            $table->string('tracker', 30)->default('mindar');
            $table->string('tracker_version', 20)->nullable();
            $table->string('target_path', 1000)->nullable();
            $table->unsignedInteger('feature_points')->nullable();
            $table->timestamp('compiled_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ar_targets');
    }
};
