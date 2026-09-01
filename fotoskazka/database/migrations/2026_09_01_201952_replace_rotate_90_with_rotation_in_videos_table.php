<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->integer('rotation')->default(0)->after('type');
        });

        DB::table('videos')->where('rotate_90', true)->update(['rotation' => 90]);

        Schema::table('videos', function (Blueprint $table) {
            $table->dropColumn('rotate_90');
        });
    }

    public function down(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->boolean('rotate_90')->default(false)->after('type');
        });

        DB::table('videos')->where('rotation', 90)->update(['rotate_90' => true]);

        Schema::table('videos', function (Blueprint $table) {
            $table->dropColumn('rotation');
        });
    }
};
