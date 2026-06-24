<?php

namespace App\Filament\Resources\Categories\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
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
                            ]),
                        TextInput::make('sort_order')
                            ->integer()
                            ->default(0),
                    ]),
            ]);
    }
}
