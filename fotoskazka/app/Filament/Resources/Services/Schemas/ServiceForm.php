<?php

namespace App\Filament\Resources\Services\Schemas;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ServiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->schema([
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('slug')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Select::make('category_id')
                            ->relationship('category', 'name')
                            ->preload()
                            ->nullable(),
                        Select::make('cover_media_id')
                            ->relationship('cover', 'title')
                            ->preload()
                            ->nullable()
                            ->label('Cover'),
                        TextInput::make('price_from')
                            ->numeric()
                            ->prefix('₽')
                            ->nullable(),
                        Toggle::make('is_published')
                            ->default(true),
                        TextInput::make('sort_order')
                            ->integer()
                            ->default(0),
                    ]),
                Section::make('SEO')
                    ->schema([
                        TextInput::make('seo_title')
                            ->maxLength(255),
                        Textarea::make('seo_description'),
                    ]),
                Section::make('Description')
                    ->schema([
                        Textarea::make('short_description'),
                        RichEditor::make('description'),
                    ]),
            ]);
    }
}
