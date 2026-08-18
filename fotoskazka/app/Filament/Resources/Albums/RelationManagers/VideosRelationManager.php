<?php

namespace App\Filament\Resources\Albums\RelationManagers;

use App\Models\Video;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VideosRelationManager extends RelationManager
{
    protected static string $relationship = 'videos';

    protected static ?string $title = 'Видео';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Название')
                    ->searchable()
                    ->limit(40),
                TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'vertical' ? 'info' : 'warning')
                    ->formatStateUsing(fn (string $state): string => $state === 'vertical' ? 'Вертикальное' : 'Горизонтальное')
                    ->label('Формат'),
                TextColumn::make('caption')
                    ->label('Подпись')
                    ->limit(40),
                TextColumn::make('pivot.sort_order')
                    ->label('Порядок'),
            ])
            ->headerActions([
                Action::make('attachVideo')
                    ->label('Прикрепить видео')
                    ->icon('heroicon-o-plus')
                    ->form([
                        Select::make('video_id')
                            ->label('Видео')
                            ->options(fn () => Video::query()
                                ->where('is_active', true)
                                ->orderBy('sort_order')
                                ->pluck('title', 'id'))
                            ->searchable()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(fn ($state, $set) => $set('sort_order', (int) (Video::find($state)?->sort_order ?? 0))),
                        TextInput::make('sort_order')
                            ->label('Порядок')
                            ->integer()
                            ->default(0),
                    ])
                    ->action(function (array $data) {
                        $this->ownerRecord->videos()->syncWithoutDetaching([
                            $data['video_id'] => [
                                'sort_order' => $data['sort_order'] ?? 0,
                                'caption' => null,
                            ],
                        ]);
                    }),
            ])
            ->recordActions([
                Action::make('editPivot')
                    ->label('Редактировать')
                    ->icon('heroicon-o-pencil')
                    ->form([
                        TextInput::make('caption')
                            ->label('Подпись')
                            ->maxLength(255),
                        TextInput::make('sort_order')
                            ->label('Порядок')
                            ->integer()
                            ->default(0),
                    ])
                    ->fillForm(fn ($record): array => [
                        'caption' => $record->pivot->caption,
                        'sort_order' => $record->pivot->sort_order,
                    ])
                    ->action(function ($record, array $data) {
                        $this->ownerRecord->videos()->updateExistingPivot($record->id, $data);
                    }),
                Action::make('detach')
                    ->label('Открепить')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn ($record) => $this->ownerRecord->videos()->detach($record->id)),
            ])
            ->reorderable('sort_order');
    }
}
