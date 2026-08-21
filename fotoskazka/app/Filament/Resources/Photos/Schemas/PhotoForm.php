<?php

namespace App\Filament\Resources\Photos\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class PhotoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->schema([
                        Select::make('album_id')
                            ->relationship('album', 'title')
                            ->preload()
                            ->searchable()
                            ->required(),
                        Select::make('media_id')
                            ->relationship('media', 'title')
                            ->preload()
                            ->searchable()
                            ->required(),
                        TextInput::make('caption')
                            ->maxLength(255),
                        TextInput::make('sort_order')
                            ->integer()
                            ->default(0),
                    ]),
            ]);
    }
}
