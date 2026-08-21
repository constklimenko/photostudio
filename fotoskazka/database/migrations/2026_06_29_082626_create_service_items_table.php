<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->boolean('is_included')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('service_id');
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_items');
    }
};
