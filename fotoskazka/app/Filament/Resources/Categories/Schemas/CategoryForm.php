<?php

namespace App\Filament\Resources\Categories\Schemas;

use App\Models\Category;
use App\Services\CategoryTreeService;
use Filament\Forms\Components\Hidden;
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
use Illuminate\Support\Str;

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
                            ->maxLength(255)
                            ->live(true)
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $slugManual = $get('_slug_manual');
                                if ($slugManual && $slugManual !== '') {
                                    return;
                                }
                                $type = $get('type') ?? 'service';
                                $base = Str::slug($state);
                                $slug = $base;
                                $counter = 1;
                                while (Category::where('slug', $slug)->where('type', $type)->exists()) {
                                    $slug = $base.'-'.$counter++;
                                }
                                $set('slug', $slug);
                            }),
                        TextInput::make('slug')
                            ->required()
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
                Section::make('Что входит')
                    ->schema([
                        Select::make('items')
                            ->multiple()
                            ->relationship('items', 'label')
                            ->preload()
                            ->searchable()
                            ->createOptionForm([
                                TextInput::make('label')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('subtitle')
                                    ->maxLength(255)
                                    ->nullable(),
                                Select::make('icon_id')
                                    ->relationship('icon', 'name')
                                    ->preload()
                                    ->nullable()
                                    ->searchable(),
                            ])
                            ->columnSpanFull(),
                    ]),
                Section::make('Примеры работ')
                    ->schema([
                        TextInput::make('examples_title')
                            ->maxLength(255)
                            ->nullable()
                            ->placeholder('Примеры работ')
                            ->columnSpanFull(),
                        Select::make('albums')
                            ->multiple()
                            ->relationship('albums', 'title')
                            ->preload()
                            ->searchable()
                            ->columnSpanFull(),
                        Toggle::make('show_album_photos')
                            ->label('Показать первый альбом блоком с фото')
                            ->helperText('Отобразить выбранный альбом как сетку фотографий вместо карточки')
                            ->live()
                            ->columnSpanFull(),
                        Select::make('featured_album_id')
                            ->label('Альбом для отображения блоком')
                            ->relationship('featuredAlbum', 'title')
                            ->preload()
                            ->searchable()
                            ->nullable()
                            ->columnSpanFull(),
                    ]),
                Section::make('Видео')
                    ->schema([
                        Select::make('videos')
                            ->multiple()
                            ->relationship('videos', 'title')
                            ->preload()
                            ->searchable()
                            ->columnSpanFull(),
                    ]),
                Section::make('Кнопка CTA')
                    ->schema([
                        Select::make('cta_album_id')
                            ->label('Альбом для кнопки')
                            ->relationship('ctaAlbum', 'title')
                            ->preload()
                            ->searchable()
                            ->nullable(),
                        TextInput::make('cta_button_text')
                            ->label('Текст кнопки')
                            ->maxLength(255)
                            ->nullable()
                            ->placeholder('Посмотреть варианты обложек'),
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
