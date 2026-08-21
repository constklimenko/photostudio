<?php

namespace App\Filament\Resources\ServiceItems\Schemas;

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
                Toggle::make('is_included')
                    ->default(true)
                    ->label('По умолчанию включено'),
                TextInput::make('sort_order')
                    ->integer()
                    ->default(0),
            ]);
    }
}
