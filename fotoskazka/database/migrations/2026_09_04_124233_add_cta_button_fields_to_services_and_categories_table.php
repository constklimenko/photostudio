<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->foreignId('cta_album_id')->nullable()->after('featured_album_id')->constrained('albums')->nullOnDelete();
            $table->string('cta_button_text', 255)->nullable()->after('cta_album_id');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->foreignId('cta_album_id')->nullable()->after('featured_album_id')->constrained('albums')->nullOnDelete();
            $table->string('cta_button_text', 255)->nullable()->after('cta_album_id');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropForeign(['cta_album_id']);
            $table->dropColumn(['cta_album_id', 'cta_button_text']);
        });

        Schema::table('services', function (Blueprint $table) {
            $table->dropForeign(['cta_album_id']);
            $table->dropColumn(['cta_album_id', 'cta_button_text']);
        });
    }
};
