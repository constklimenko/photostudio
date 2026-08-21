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
        $defaultDisk = config('filesystems.default_media_disk', 'public');

        return $schema
            ->components([
                Grid::make(2)
                    ->schema([
                        TextInput::make('title')
                            ->maxLength(255),
                        TextInput::make('alt_text')
                            ->maxLength(255),
                        FileUpload::make('file_path')
                            ->required()
                            ->disk($defaultDisk)
                            ->visibility('public')
                            ->image()
                            ->maxSize(51200),
                        TextInput::make('collection')
                            ->maxLength(100),
                    ]),
            ]);
    }
}
