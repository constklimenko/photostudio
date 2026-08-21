<?php

namespace App\Filament\Resources\SocialLinks\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class SocialLinksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->label('Название'),
                TextColumn::make('icon')
                    ->label('Иконка'),
                TextColumn::make('url')
                    ->limit(50)
                    ->label('Ссылка'),
                IconColumn::make('is_active')
                    ->boolean()
                    ->label('Активно'),
                TextColumn::make('sort_order')
                    ->sortable()
                    ->label('Порядок'),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Активность'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('sort_order');
    }
}
