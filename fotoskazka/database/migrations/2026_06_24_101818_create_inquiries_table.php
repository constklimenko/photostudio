<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inquiries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('phone', 50);
            $table->string('email')->nullable();
            $table->text('message')->nullable();
            $table->date('shooting_date')->nullable();
            $table->enum('status', ['new', 'in_progress', 'completed', 'cancelled']);
            $table->timestamps();

            $table->index('user_id');
            $table->index('service_id');
            $table->index('status');
            $table->index('phone');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inquiries');
    }
};
