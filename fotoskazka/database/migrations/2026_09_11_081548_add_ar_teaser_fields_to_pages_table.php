<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->boolean('ar_teaser_enabled')->default(true)->after('menu_title');
            $table->string('ar_teaser_title')->nullable()->after('ar_teaser_enabled');
            $table->text('ar_teaser_subtitle')->nullable()->after('ar_teaser_title');
            $table->foreignId('ar_teaser_media_id')->nullable()->after('ar_teaser_subtitle')->constrained('media')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropForeign(['ar_teaser_media_id']);
            $table->dropColumn(['ar_teaser_enabled', 'ar_teaser_title', 'ar_teaser_subtitle', 'ar_teaser_media_id']);
        });
    }
};
