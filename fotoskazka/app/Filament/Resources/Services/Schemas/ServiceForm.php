<?php

namespace App\Filament\Resources\Services\Schemas;

use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

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
                            ->maxLength(255)
                            ->live(true)
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                if ($get('_slug_manual')) {
                                    return;
                                }
                                $set('slug', Str::slug($state));
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
                            ->label('Cover'),
                        TextInput::make('price_from')
                            ->numeric()
                            ->prefix('₽')
                            ->nullable(),
                        Textarea::make('price_note')
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
                Section::make('Что входит')
                    ->schema([
                        Repeater::make('items')
                            ->relationship()
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('label')
                                            ->required()
                                            ->maxLength(255)
                                            ->label('Пункт'),
                                        Toggle::make('is_included')
                                            ->default(true)
                                            ->label('Включено'),
                                    ]),
                            ])
                            ->orderColumn('sort_order')
                            ->reorderable()
                            ->addActionLabel('Добавить пункт')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
