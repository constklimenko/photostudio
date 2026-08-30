<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_service_item', function (Blueprint $table): void {
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_item_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_included')->default(true);
            $table->integer('sort_order')->default(0);
            $table->primary(['category_id', 'service_item_id']);
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_service_item');
    }
};
