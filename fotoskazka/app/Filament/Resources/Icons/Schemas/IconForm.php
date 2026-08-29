<?php

namespace App\Filament\Resources\Icons\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class IconForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->label('Название'),
                FileUpload::make('file_path')
                    ->required()
                    ->disk('public')
                    ->directory('icons')
                    ->visibility('public')
                    ->image()
                    ->imageEditor()
                    ->maxSize(2048)
                    ->label('Файл'),
            ]);
    }
}
