<?php

namespace App\Filament\Resources\ServiceItems\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ServiceItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('label')
                    ->required()
                    ->maxLength(255)
                    ->label('Пункт'),
                TextInput::make('subtitle')
                    ->maxLength(255)
                    ->nullable()
                    ->label('Подзаголовок'),
                Select::make('icon_id')
                    ->relationship('icon', 'name')
                    ->preload()
                    ->nullable()
                    ->searchable()
                    ->label('Иконка'),
                Toggle::make('is_included')
                    ->default(true)
                    ->label('По умолчанию включено'),
                TextInput::make('sort_order')
                    ->integer()
                    ->default(0),
            ]);
    }
}
