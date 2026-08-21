<?php

namespace App\Filament\Resources\Testimonials\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class TestimonialForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->schema([
                        TextInput::make('client_name')
                            ->required()
                            ->maxLength(255),
                        Select::make('media_id')
                            ->relationship('photo', 'title')
                            ->preload()
                            ->nullable()
                            ->label('Фотография'),
                        Toggle::make('is_published')
                            ->default(true),
                        TextInput::make('sort_order')
                            ->integer()
                            ->default(0),
                    ]),
                Textarea::make('content')
                    ->required(),
            ]);
    }
}
