<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('album_service', function (Blueprint $table) {
            $table->foreignId('album_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();

            $table->primary(['album_id', 'service_id']);
            $table->index('service_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('album_service');
    }
};
