<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->boolean('show_on_home')->default(false)->after('is_published');
            $table->index('show_on_home');
        });

        Schema::table('services', function (Blueprint $table) {
            $table->boolean('show_on_home')->default(false)->after('is_published');
            $table->index('show_on_home');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex(['show_on_home']);
            $table->dropColumn('show_on_home');
        });

        Schema::table('services', function (Blueprint $table) {
            $table->dropIndex(['show_on_home']);
            $table->dropColumn('show_on_home');
        });
    }
};
