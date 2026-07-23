<?php

namespace App\Filament\Resources\FaqItems\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class FaqItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('question')
                    ->searchable()
                    ->sortable()
                    ->limit(80)
                    ->label('Вопрос'),
                TextColumn::make('answer')
                    ->limit(100)
                    ->searchable()
                    ->label('Ответ'),
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
