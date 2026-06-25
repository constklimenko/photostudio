<?php

namespace App\Filament\Resources\Albums\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AlbumForm
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
                        Select::make('type')
                            ->required()
                            ->default('portfolio')
                            ->options([
                                'portfolio' => 'Портфолио',
                                'project' => 'Проект (съёмка)',
                                'homepage' => 'Главная страница',
                                'service' => 'Услуга',
                                'client' => 'Клиентская галерея',
                            ])
                            ->live(),
                        Select::make('project_id')
                            ->relationship('project', 'title')
                            ->preload()
                            ->searchable()
                            ->nullable()
                            ->hidden(fn ($get) => $get('type') !== 'project')
                            ->label('Проект'),
                        Select::make('cover_media_id')
                            ->relationship('cover', 'title')
                            ->preload()
                            ->nullable()
                            ->label('Обложка'),
                        Toggle::make('is_featured')
                            ->label('Избранный'),
                        Toggle::make('is_published')
                            ->default(true)
                            ->label('Опубликован'),
                        TextInput::make('sort_order')
                            ->integer()
                            ->default(0)
                            ->label('Порядок сортировки'),
                    ]),
                Section::make('SEO')
                    ->schema([
                        TextInput::make('seo_title')
                            ->maxLength(255),
                        Textarea::make('seo_description'),
                    ]),
                Section::make('Описание')
                    ->schema([
                        Textarea::make('description'),
                    ]),
            ]);
    }
}
