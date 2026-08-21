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
                Section::make('Основная информация')
                    ->columns(2)
                    ->schema([
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->label('Заголовок')
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
                        TextInput::make('menu_title')
                            ->maxLength(255)
                            ->label('Название в меню')
                            ->helperText('Если не заполнено — используется заголовок'),
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

                Section::make('Заголовок страницы')
                    ->columns(2)
                    ->schema([
                        TextInput::make('subtitle')
                            ->maxLength(255)
                            ->label('Подзаголовок'),
                        Select::make('cover_media_id')
                            ->relationship('cover', 'title')
                            ->preload()
                            ->nullable()
                            ->label('Обложка'),
                        RichEditor::make('content')
                            ->columnSpanFull()
                            ->label('Описание'),
                    ]),

                Section::make('Главная страница')
                    ->schema([
                        Toggle::make('show_on_home')
                            ->label('Показывать блок на главной')
                            ->live(true),
                        Grid::make(2)
                            ->schema([
                                TextInput::make('home_title')
                                    ->maxLength(255)
                                    ->label('Заголовок блока на главной')
                                    ->visible(fn (callable $get) => $get('show_on_home')),
                                TextInput::make('home_sort_order')
                                    ->integer()
                                    ->default(0)
                                    ->label('Порядок на главной')
                                    ->visible(fn (callable $get) => $get('show_on_home')),
                                Textarea::make('home_subtitle')
                                    ->label('Подзаголовок блока на главной')
                                    ->visible(fn (callable $get) => $get('show_on_home'))
                                    ->columnSpanFull(),
                            ]),
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
            ]);
    }
}
