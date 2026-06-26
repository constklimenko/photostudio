<?php

namespace App\Filament\Resources\Inquiries\Schemas;

use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class InquiryForm
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
                        TextInput::make('phone')
                            ->required()
                            ->tel()
                            ->maxLength(50),
                        TextInput::make('email')
                            ->email()
                            ->maxLength(255),
                        Select::make('service_id')
                            ->relationship('service', 'title')
                            ->preload()
                            ->nullable()
                            ->label('Service'),
                        Select::make('user_id')
                            ->relationship('user', 'name')
                            ->preload()
                            ->searchable()
                            ->nullable()
                            ->label('User'),
                        Select::make('status')
                            ->required()
                            ->default('new')
                            ->options([
                                'new' => 'New',
                                'in_progress' => 'In Progress',
                                'completed' => 'Completed',
                                'cancelled' => 'Cancelled',
                            ]),
                        DatePicker::make('shooting_date'),
                    ]),
                Textarea::make('message'),
                Checkbox::make('agreed_to_terms')
                    ->label('Согласен на обработку персональных данных')
                    ->required()
                    ->default(false),
            ]);
    }
}
