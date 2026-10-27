<?php

namespace App\Filament\Resources\ArTargets\Tables;

use App\Models\ArTarget;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Config;

class ArTargetsTable
{
    public const WEAK_FEATURE_POINTS = 60;

    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->withCount('contents'))
            ->columns([
                ImageColumn::make('marker_path')
                    ->disk(Config::get('filesystems.ar_disk', 'public'))
                    ->height(48)
                    ->label('Фото'),
                TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->limit(60)
                    ->label('Название'),
                TextColumn::make('project.title')
                    ->searchable()
                    ->toggleable()
                    ->label('Проект'),
                TextColumn::make('token')
                    ->formatStateUsing(fn (string $state): string => '/ar/'.$state)
                    ->copyable()
                    ->copyableState(fn (ArTarget $record): string => url('/ar/'.$record->token))
                    ->searchable()
                    ->label('Ссылка'),
                TextColumn::make('contents_count')
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'success' : 'danger')
                    ->label('Видео'),
                TextColumn::make('feature_points')
                    ->badge()
                    ->color(fn (?int $state): string => $state !== null && $state < self::WEAK_FEATURE_POINTS ? 'warning' : 'gray')
                    ->tooltip('Опорные точки распознавания; меньше 60 — фото слабое')
                    ->toggleable()
                    ->label('Точки'),
                IconColumn::make('is_active')
                    ->boolean()
                    ->label('Включена'),
                TextColumn::make('created_at')
                    ->dateTime('d.m.Y')
                    ->sortable()
                    ->toggleable()
                    ->label('Создано'),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Ссылка включена'),
                SelectFilter::make('project_id')
                    ->relationship('project', 'title')
                    ->searchable()
                    ->preload()
                    ->label('Проект'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('activate')
                        ->label('Включить ссылки')
                        ->icon('heroicon-o-check-circle')
                        ->action(fn (Collection $records) => $records->each->update(['is_active' => true]))
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('deactivate')
                        ->label('Выключить ссылки')
                        ->icon('heroicon-o-x-circle')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(fn (Collection $records) => $records->each->update(['is_active' => false]))
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
