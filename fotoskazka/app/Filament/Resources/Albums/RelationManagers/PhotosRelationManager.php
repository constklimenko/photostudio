<?php

namespace App\Filament\Resources\Albums\RelationManagers;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PhotosRelationManager extends RelationManager
{
    protected static string $relationship = 'photos';

    protected static ?string $title = 'Фотографии';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('caption')->label('Подпись')->maxLength(255),
                TextInput::make('sort_order')->label('Порядок')->integer()->default(0),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('media.thumbnail_path')
                    ->label('Превью')
                    ->disk('thumbnails')
                    ->square()
                    ->size(80),
                TextColumn::make('media.title')
                    ->label('Название')
                    ->searchable()
                    ->limit(40),
                TextColumn::make('caption')
                    ->label('Подпись')
                    ->searchable()
                    ->limit(40),
                TextColumn::make('sort_order')
                    ->label('Порядок')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                //
            ])
            ->recordActions([
                Action::make('setCover')
                    ->label('Сделать обложкой')
                    ->icon('heroicon-o-photo')
                    ->action(function ($record) {
                        $this->ownerRecord->update(['cover_media_id' => $record->media_id]);
                    }),
                EditAction::make()
                    ->label('Редактировать'),
                Action::make('delete')
                    ->label('Удалить')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn ($record) => $record->delete()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('sort_order', 'asc')
            ->reorderable('sort_order');
    }
}
