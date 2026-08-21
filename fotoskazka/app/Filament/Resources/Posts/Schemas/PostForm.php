<?php

namespace App\Filament\Resources\Posts\Schemas;

use App\Models\Post;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class PostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->schema([
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->live(true)
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                if ($get('_slug_manual')) {
                                    return;
                                }
                                $base = Str::slug($state);
                                $slug = $base;
                                $counter = 1;
                                while (Post::where('slug', $slug)->exists()) {
                                    $slug = $base.'-'.$counter++;
                                }
                                $set('slug', $slug);
                            }),
                        TextInput::make('slug')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->live(true)
                            ->afterStateUpdated(function ($state, callable $set) {
                                $set('_slug_manual', '1');
                                $slugged = Str::slug($state);
                                if ($state !== $slugged) {
                                    $set('slug', $slugged);
                                }
                            }),
                        Hidden::make('_slug_manual'),
                        Select::make('category_id')
                            ->relationship('category', 'name')
                            ->preload()
                            ->nullable(),
                        Select::make('cover_media_id')
                            ->relationship('cover', 'title')
                            ->preload()
                            ->nullable()
                            ->label('Обложка'),
                        Toggle::make('is_published')
                            ->default(true),
                        DateTimePicker::make('published_at'),
                    ]),
                Section::make('Альбомы')
                    ->schema([
                        Select::make('albums')
                            ->relationship('albums', 'title')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->label('Привязанные альбомы'),
                    ]),
                Section::make('Видео')
                    ->schema([
                        Select::make('videos')
                            ->relationship('videos', 'title')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->label('Привязанные видео'),
                    ]),
                Section::make('SEO')
                    ->schema([
                        TextInput::make('seo_title')
                            ->maxLength(255),
                        Textarea::make('seo_description'),
                    ]),
                Section::make('Содержание')
                    ->schema([
                        Textarea::make('excerpt'),
                        RichEditor::make('content')
                            ->required(),
                    ]),
            ]);
    }
}
