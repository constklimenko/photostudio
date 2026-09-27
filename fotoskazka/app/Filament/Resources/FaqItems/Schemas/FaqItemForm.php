<?php

namespace App\Filament\Resources\FaqItems\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FaqItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->schema([
                        TextInput::make('question')
                            ->required()
                            ->maxLength(255)
                            ->label('Вопрос'),
                        Toggle::make('is_active')
                            ->default(true)
                            ->label('Активно'),
                        TextInput::make('sort_order')
                            ->integer()
                            ->default(0)
                            ->label('Порядок'),
                    ]),
                Textarea::make('answer')
                    ->required()
                    ->rows(5)
                    ->label('Ответ'),
                Section::make('Привязка к каталогу услуг')
                    ->schema([
                        Select::make('services')
                            ->multiple()
                            ->relationship('services', 'title')
                            ->preload()
                            ->searchable()
                            ->label('Услуги')
                            ->columnSpanFull(),
                        Select::make('categories')
                            ->multiple()
                            ->relationship('categories', 'name')
                            ->preload()
                            ->searchable()
                            ->label('Категории услуг')
                            ->helperText('Доступны только категории типа «Услуга»')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
