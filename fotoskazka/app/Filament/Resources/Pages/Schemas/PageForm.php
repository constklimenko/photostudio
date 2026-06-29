<?php

namespace App\Filament\Resources\Pages\Schemas;

use App\Models\Page;
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

class PageForm
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
                                while (Page::where('slug', $slug)->exists()) {
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
                        Select::make('cover_media_id')
                            ->relationship('cover', 'title')
                            ->preload()
                            ->nullable()
                            ->label('Cover'),
                        Toggle::make('is_published')
                            ->default(true),
                        TextInput::make('sort_order')
                            ->integer()
                            ->default(0),
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
                Section::make('SEO')
                    ->schema([
                        TextInput::make('seo_title')
                            ->maxLength(255),
                        Textarea::make('seo_description'),
                    ]),
                Section::make('Content')
                    ->schema([
                        Textarea::make('excerpt'),
                        RichEditor::make('content'),
                    ]),
            ]);
    }
}
