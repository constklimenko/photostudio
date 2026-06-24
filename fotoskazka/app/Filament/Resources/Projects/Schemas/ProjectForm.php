<?php

namespace App\Filament\Resources\Projects\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->schema([
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('slug')
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Select::make('client_id')
                            ->relationship('client', 'name')
                            ->preload()
                            ->searchable()
                            ->nullable()
                            ->label('Client'),
                        Select::make('manager_id')
                            ->relationship('manager', 'name')
                            ->preload()
                            ->searchable()
                            ->nullable()
                            ->label('Manager'),
                        Select::make('type')
                            ->required()
                            ->options([
                                'individual' => 'Individual',
                                'family' => 'Family',
                                'event' => 'Event',
                                'wedding' => 'Wedding',
                                'school' => 'School',
                                'kindergarten' => 'Kindergarten',
                            ]),
                        Select::make('status')
                            ->required()
                            ->default('draft')
                            ->options([
                                'draft' => 'Draft',
                                'active' => 'Active',
                                'completed' => 'Completed',
                                'archived' => 'Archived',
                            ]),
                        DatePicker::make('shooting_date'),
                    ]),
                Section::make('Description')
                    ->schema([
                        Textarea::make('description'),
                    ]),
            ]);
    }
}
