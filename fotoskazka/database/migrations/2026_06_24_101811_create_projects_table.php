<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('slug')->nullable()->unique();
            $table->enum('type', ['individual', 'family', 'event', 'wedding', 'school', 'kindergarten']);
            $table->text('description')->nullable();
            $table->date('shooting_date')->nullable();
            $table->enum('status', ['draft', 'active', 'completed', 'archived']);
            $table->timestamps();

            $table->index('client_id');
            $table->index('manager_id');
            $table->index('type');
            $table->index('status');
            $table->index('shooting_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
