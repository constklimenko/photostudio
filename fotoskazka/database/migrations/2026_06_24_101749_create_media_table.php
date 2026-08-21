<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->string('alt_text')->nullable();
            $table->string('disk', 50);
            $table->string('file_path', 1000);
            $table->string('thumbnail_path', 1000)->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('collection', 100)->nullable();
            $table->timestamps();

            $table->index('disk');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
