<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->foreignId('parent_id')
                ->nullable()
                ->after('slug')
                ->constrained('categories')
                ->nullOnDelete();
            $table->foreignId('cover_media_id')
                ->nullable()
                ->after('parent_id')
                ->constrained('media')
                ->nullOnDelete();
            $table->text('description')
                ->nullable()
                ->after('cover_media_id');
            $table->decimal('price_from', 10, 2)
                ->nullable()
                ->after('description');
            $table->text('price_note')
                ->nullable()
                ->after('price_from');
            $table->string('seo_title')
                ->nullable()
                ->after('price_note');
            $table->text('seo_description')
                ->nullable()
                ->after('seo_title');
            $table->boolean('is_published')
                ->default(true)
                ->after('seo_description');

            $table->index('parent_id');
            $table->index('cover_media_id');
            $table->index('is_published');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_id');
            $table->dropConstrainedForeignId('cover_media_id');

            $table->dropColumn([
                'description',
                'price_from',
                'price_note',
                'seo_title',
                'seo_description',
                'is_published',
            ]);
        });
    }
};
