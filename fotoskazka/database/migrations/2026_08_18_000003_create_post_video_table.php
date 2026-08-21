<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_video', function (Blueprint $table): void {
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('video_id')->constrained()->cascadeOnDelete();
            $table->primary(['post_id', 'video_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_video');
    }
};
