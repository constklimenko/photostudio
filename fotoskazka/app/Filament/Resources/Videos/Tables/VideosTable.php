<?php

namespace App\Filament\Resources\Videos\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class VideosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->limit(80)
                    ->label('Название'),
                TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'vertical' => 'info',
                        'horizontal' => 'warning',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'vertical' => 'Вертикальное',
                        'horizontal' => 'Горизонтальное',
                    })
                    ->label('Формат'),
                IconColumn::make('is_upload')
                    ->boolean()
                    ->label('Файл')
                    ->tooltip('Загруженный файл, а не ссылка'),
                IconColumn::make('rotate_90')
                    ->boolean()
                    ->label('90°')
                    ->tooltip('Повёрнуто на 90°'),
                IconColumn::make('show_on_home')
                    ->boolean()
                    ->label('На главной'),
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
                TernaryFilter::make('show_on_home')
                    ->label('Показывать на главной'),
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
