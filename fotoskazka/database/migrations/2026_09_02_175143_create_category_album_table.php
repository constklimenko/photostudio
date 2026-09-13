<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_album', function (Blueprint $table) {
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('album_id')->constrained()->cascadeOnDelete();

            $table->primary(['category_id', 'album_id']);
            $table->index('album_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_album');
    }
};
