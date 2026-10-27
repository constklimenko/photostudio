<?php

namespace App\Filament\Resources\ArTargets\Schemas;

use App\Models\ArTarget;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ViewField;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ArTargetForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Основное')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('title')
                                    ->required()
                                    ->maxLength(255)
                                    ->label('Название')
                                    ->helperText('Для админки, например «11А, общее фото»'),
                                Select::make('project_id')
                                    ->relationship('project', 'title')
                                    ->searchable()
                                    ->preload()
                                    ->nullable()
                                    ->label('Проект'),
                                Toggle::make('is_active')
                                    ->default(true)
                                    ->label('Ссылка включена')
                                    ->helperText('Выключенная ссылка перестаёт открываться, файлы сохраняются'),
                                TextEntry::make('public_url')
                                    ->label('Публичная ссылка (для QR-кода)')
                                    ->state(fn (?ArTarget $record): ?string => $record ? url('/ar/'.$record->token) : null)
                                    ->copyable()
                                    ->hiddenOn('create'),
                            ]),
                    ]),
                Section::make('Фото-таргет')
                    ->description('Фото, которое будет распознавать камера. Таргет собирается прямо в браузере (около 10 секунд).')
                    ->schema([
                        ViewField::make('marker_compiler')
                            ->view('filament.forms.components.ar-marker-compiler')
                            ->dehydrated(false)
                            ->hiddenLabel(),
                    ]),
            ]);
    }
}
