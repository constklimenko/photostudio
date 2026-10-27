<?php

namespace App\Filament\Resources\ArTargets\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;

class ContentsRelationManager extends RelationManager
{
    protected static string $relationship = 'contents';

    protected static ?string $title = 'Видео';

    protected static ?string $modelLabel = 'видео';

    public function form(Schema $schema): Schema
    {
        $target = $this->getOwnerRecord();

        return $schema
            ->components([
                FileUpload::make('file_path')
                    ->label('Файл видео')
                    ->disk($target->disk)
                    ->directory("ar/targets/{$target->id}/contents")
                    ->acceptedFileTypes(['video/mp4'])
                    ->maxSize(102400)
                    ->required()
                    ->helperText('MP4 (H.264) до 100 МБ. Пропорции кадра — как у фото, иначе видео ляжет на фото с полями.')
                    ->visibleOn('create')
                    ->columnSpanFull(),
                Toggle::make('has_sound')
                    ->default(true)
                    ->label('Есть звук')
                    ->helperText('На странице появится запрос на включение звука'),
                Toggle::make('is_active')
                    ->default(true)
                    ->label('Показывать'),
                TextInput::make('sort_order')
                    ->integer()
                    ->default(0)
                    ->label('Порядок')
                    ->helperText('Показывается первое включённое видео'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('file_path')
                    ->formatStateUsing(fn (string $state): string => basename($state))
                    ->label('Файл'),
                TextColumn::make('file_size')
                    ->formatStateUsing(fn (?int $state): string => $state ? Number::fileSize($state, 1) : '—')
                    ->label('Размер'),
                IconColumn::make('has_sound')
                    ->boolean()
                    ->label('Звук'),
                IconColumn::make('is_active')
                    ->boolean()
                    ->label('Показывать'),
                TextColumn::make('sort_order')
                    ->label('Порядок'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Загрузить видео')
                    ->mutateDataUsing(function (array $data): array {
                        $disk = $this->getOwnerRecord()->disk;
                        $storage = Storage::disk($disk);

                        return [
                            ...$data,
                            'type' => 'video',
                            'disk' => $disk,
                            'mime_type' => $storage->mimeType($data['file_path']) ?: null,
                            'file_size' => $storage->size($data['file_path']),
                        ];
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->modalDescription('Файл видео будет удалён с диска.'),
            ])
            ->defaultSort('sort_order');
    }
}
