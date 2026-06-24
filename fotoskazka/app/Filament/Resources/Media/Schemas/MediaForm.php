<?php

namespace App\Filament\Resources\Media\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class MediaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->schema([
                        TextInput::make('title')
                            ->maxLength(255),
                        TextInput::make('alt_text')
                            ->maxLength(255),
                        TextInput::make('disk')
                            ->required()
                            ->maxLength(50),
                        FileUpload::make('file_path')
                            ->required()
                            ->disk('public'),
                        TextInput::make('thumbnail_path')
                            ->maxLength(1000),
                        TextInput::make('mime_type')
                            ->maxLength(255),
                        TextInput::make('width')
                            ->integer()
                            ->nullable(),
                        TextInput::make('height')
                            ->integer()
                            ->nullable(),
                        TextInput::make('file_size')
                            ->integer()
                            ->nullable()
                            ->label('File Size (bytes)'),
                        TextInput::make('collection')
                            ->maxLength(100),
                    ]),
            ]);
    }
}
