<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_faq_item', function (Blueprint $table) {
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('faq_item_id')->constrained()->cascadeOnDelete();

            $table->primary(['category_id', 'faq_item_id']);
            $table->index('faq_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_faq_item');
    }
};
