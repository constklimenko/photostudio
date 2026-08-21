<?php

namespace App\Filament\Resources\Categories\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->searchable(),
                TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state === 'service' ? 'Услуга' : 'Блог')
                    ->color(fn ($state) => $state === 'service' ? 'success' : 'info'),
                TextColumn::make('sort_order')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        'service' => 'Услуга',
                        'post' => 'Блог',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
