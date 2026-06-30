<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE inquiries MODIFY COLUMN status ENUM('new', 'in_progress', 'completed', 'cancelled') NOT NULL DEFAULT 'new'");
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE inquiries MODIFY COLUMN status ENUM('new', 'in_progress', 'completed', 'cancelled') NOT NULL");
        }
    }
};
