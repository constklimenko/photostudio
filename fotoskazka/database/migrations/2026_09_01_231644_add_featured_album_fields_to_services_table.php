<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->boolean('show_album_photos')->default(false)->after('examples_title');
            $table->foreignId('featured_album_id')
                ->nullable()
                ->after('show_album_photos')
                ->constrained('albums')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropConstrainedForeignId('featured_album_id');
            $table->dropColumn('show_album_photos');
        });
    }
};
