<?php

namespace App\Filament\Resources\ServiceItems\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ServiceItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('icon.file_path')
                    ->disk('public')
                    ->label('Иконка')
                    ->circular()
                    ->default('—'),
                TextColumn::make('label')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('subtitle')
                    ->label('Подзаголовок')
                    ->limit(50)
                    ->placeholder('—'),
                IconColumn::make('is_included')
                    ->boolean()
                    ->label('Включено'),
                TextColumn::make('services_count')
                    ->label('Услуг')
                    ->counts('services')
                    ->sortable(),
                TextColumn::make('sort_order')
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_included'),
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
