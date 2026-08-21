<?php

namespace App\Filament\Resources\Albums\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class AlbumsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->badge()
                    ->sortable()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'portfolio' => 'Портфолио',
                        'project' => 'Съёмка',
                        'homepage' => 'Главная',
                        'service' => 'Услуга',
                        'client' => 'Клиент',
                        default => $state,
                    })
                    ->color(fn ($state) => match ($state) {
                        'portfolio' => 'success',
                        'project' => 'info',
                        'homepage' => 'warning',
                        'service' => 'primary',
                        'client' => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('project.title')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_featured')
                    ->boolean(),
                IconColumn::make('is_published')
                    ->boolean(),
                TextColumn::make('sort_order')
                    ->sortable(),
                TextColumn::make('photos_count')
                    ->counts('photos')
                    ->label('Фото'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        'portfolio' => 'Портфолио',
                        'project' => 'Съёмка',
                        'homepage' => 'Главная',
                        'service' => 'Услуга',
                        'client' => 'Клиент',
                    ]),
                TernaryFilter::make('is_featured'),
                TernaryFilter::make('is_published'),
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
