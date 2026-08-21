<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->text('subtitle')->nullable()->after('title');
            $table->string('home_title')->nullable()->after('content');
            $table->text('home_subtitle')->nullable()->after('home_title');
            $table->boolean('show_on_home')->default(false)->after('home_subtitle');
            $table->integer('home_sort_order')->default(0)->after('show_on_home');
            $table->string('menu_title')->nullable()->after('home_sort_order');
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn(['subtitle', 'home_title', 'home_subtitle', 'show_on_home', 'home_sort_order', 'menu_title']);
        });
    }
};
