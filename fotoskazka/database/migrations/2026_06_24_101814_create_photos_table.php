<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('album_id')->constrained()->cascadeOnDelete();
            $table->foreignId('media_id')->constrained()->cascadeOnDelete();
            $table->string('caption')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('album_id');
            $table->index('media_id');
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('photos');
    }
};
