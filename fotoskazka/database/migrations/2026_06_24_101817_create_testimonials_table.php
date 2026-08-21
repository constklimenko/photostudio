<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('testimonials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('media_id')->nullable()->constrained()->nullOnDelete();
            $table->string('client_name');
            $table->text('content');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();

            $table->index('is_published');
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('testimonials');
    }
};
