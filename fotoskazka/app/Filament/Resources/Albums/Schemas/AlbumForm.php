<?php

namespace App\Filament\Resources\Albums\Schemas;

use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

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
                            ->maxLength(255)
                            ->live(true)
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $slugManual = $get('_slug_manual');
                                if ($slugManual && $slugManual !== '') {
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
                    ]),
                Section::make('Основное')
                    ->schema([
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
