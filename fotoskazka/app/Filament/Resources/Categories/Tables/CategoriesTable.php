<?php

namespace App\Filament\Resources\Categories\Tables;

use App\Filament\Resources\Categories\Pages\CreateCategory;
use App\Filament\Resources\Categories\Pages\EditCategory;
use App\Models\Category;
use App\Services\CategoryTreeService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use LogicException;

class CategoriesTable
{
    public static function configure(Table $table): Table
    {
        $tree = app(CategoryTreeService::class)->flatten('service');

        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable()
                    ->state(fn (Category $record): string => ($tree[$record->getKey()]['indent'] ?? '').$record->name)
                    ->description(fn (Category $record): ?string => $tree[$record->getKey()]['pathLabel'] ?? null)
                    ->color(fn (Category $record): string => self::depthColor($tree[$record->getKey()]['depth'] ?? 0)),
                TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state === 'service' ? 'Услуга' : 'Блог')
                    ->color(fn ($state) => $state === 'service' ? 'success' : 'gray'),
                TextColumn::make('price_from')
                    ->label('Цена от')
                    ->formatStateUsing(fn ($state) => $state !== null
                        ? 'от '.number_format((float) $state, 0, ',', ' ').' ₽'
                        : '—'),
                ToggleColumn::make('is_published')
                    ->label('Опубликована')
                    ->onColor('success')
                    ->offColor('gray'),
                TextColumn::make('sort_order')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        'service' => 'Услуга',
                        'post' => 'Блог',
                    ]),
                SelectFilter::make('is_published')
                    ->label('Статус публикации')
                    ->options([
                        '1' => 'Опубликована',
                        '0' => 'Скрыта',
                    ]),
            ])
            ->defaultSort('sort_order')
            ->recordUrl(fn (Category $record): string => EditCategory::getUrl(['record' => $record]))
            ->recordActions([
                EditAction::make(),
                self::addChildAction(),
                self::reorderAction(-1, 'Переместить выше', 'heroicon-o-chevron-up'),
                self::reorderAction(1, 'Переместить ниже', 'heroicon-o-chevron-down'),
            ]);
    }

    protected static function addChildAction(): Action
    {
        return Action::make('addChild')
            ->label('Подкатегория')
            ->icon('heroicon-o-plus-circle')
            ->color('gray')
            ->url(fn (Category $record): string => CreateCategory::getUrl(['parent_id' => $record->getKey()]));
    }

    protected static function reorderAction(int $offset, string $label, string $icon): Action
    {
        return Action::make($offset < 0 ? 'moveUp' : 'moveDown')
            ->label($label)
            ->icon($icon)
            ->color('gray')
            ->action(function (Category $record) use ($offset): void {
                try {
                    app(CategoryTreeService::class)->move($record, $offset);

                    Notification::make()
                        ->title('Порядок изменён')
                        ->success()
                        ->send();
                } catch (LogicException $exception) {
                    Notification::make()
                        ->title($exception->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    protected static function depthColor(int $depth): string
    {
        return match ($depth) {
            0 => 'primary',
            1 => 'warning',
            2 => 'info',
            default => 'gray',
        };
    }
}
