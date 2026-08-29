<?php

namespace App\Filament\Resources\Categories\Schemas;

use App\Models\Category;
use App\Services\CategoryTreeService;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('slug')
                            ->required()
                            ->maxLength(255),
                        Select::make('type')
                            ->required()
                            ->options([
                                'service' => 'Услуга',
                                'post' => 'Блог',
                            ])
                            ->default('service')
                            ->live()
                            ->afterStateUpdated(function (Set $set, ?string $state): void {
                                if ($state === 'post') {
                                    $set('parent_id', null);
                                }
                            }),
                        Select::make('parent_id')
                            ->label('Родительская категория')
                            ->placeholder('Нет (корневая категория)')
                            ->options(function (?Category $record): array {
                                return app(CategoryTreeService::class)->options($record);
                            })
                            ->default(fn () => request()->integer('parent_id') ?: null)
                            ->preload()
                            ->searchable()
                            ->nullable()
                            ->visible(fn (Get $get): bool => (string) $get('type') === 'service'),
                        Select::make('cover_media_id')
                            ->label('Обложка')
                            ->relationship('cover', 'title')
                            ->preload()
                            ->searchable()
                            ->nullable(),
                        TextInput::make('price_from')
                            ->label('Цена от, ₽')
                            ->numeric()
                            ->nullable(),
                        Textarea::make('price_note')
                            ->rows(2)
                            ->nullable(),
                        TextInput::make('sort_order')
                            ->integer()
                            ->default(0),
                        Toggle::make('is_published')
                            ->label('Опубликована')
                            ->default(true),
                    ]),
                Section::make('Описание')
                    ->schema([
                        RichEditor::make('description')
                            ->columnSpanFull(),
                    ]),
                Section::make('SEO')
                    ->schema([
                        TextInput::make('seo_title')
                            ->maxLength(255),
                        Textarea::make('seo_description'),
                    ]),
            ]);
    }
}
