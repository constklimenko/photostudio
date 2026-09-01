<?php

namespace App\Filament\Resources\Videos\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class VideoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->schema([
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->label('Название'),
                        Select::make('type')
                            ->required()
                            ->options([
                                'horizontal' => 'Горизонтальное',
                                'vertical' => 'Вертикальное',
                            ])
                            ->default('horizontal')
                            ->label('Формат'),
                        Select::make('rotation')
                            ->default(0)
                            ->options([
                                0 => 'Без поворота',
                                90 => '90° по часовой',
                                -90 => '90° против часовой',
                            ])
                            ->label('Поворот')
                            ->helperText('Применяется к загруженному файлу: вертикальный клип будет отображаться горизонтально')
                            ->live(),
                        Toggle::make('has_sound')
                            ->default(true)
                            ->label('С сайта звук должен звучать')
                            ->helperText('При выключении видео на страницах сайта воспроизводится без звука (muted)'),
                        TextInput::make('url')
                            ->nullable()
                            ->maxLength(1000)
                            ->url()
                            ->label('Ссылка на видео (YouTube, Vimeo, Rutube)')
                            ->helperText('Либо загрузите файл ниже'),
                        FileUpload::make('file_path')
                            ->nullable()
                            ->disk('public')
                            ->visibility('public')
                            ->directory('videos')
                            ->acceptedFileTypes([
                                'video/mp4', 'video/webm', 'video/ogg',
                                'video/quicktime', 'video/x-msvideo',
                            ])
                            ->maxSize(102400)
                            ->label('Файл видео')
                            ->helperText('MP4, WebM, OGG, MOV, AVI — не более 100 МБ')
                            ->columnStart(1),
                        Toggle::make('is_active')
                            ->default(true)
                            ->label('Активно'),
                        Toggle::make('show_on_home')
                            ->default(false)
                            ->label('Показывать на главной'),
                        TextInput::make('sort_order')
                            ->integer()
                            ->default(0)
                            ->label('Порядок'),
                    ]),
                Section::make('Привязка')
                    ->schema([
                        Select::make('albums')
                            ->relationship('albums', 'title')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->label('Альбомы'),
                        Select::make('services')
                            ->relationship('services', 'title')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->label('Услуги'),
                        Select::make('posts')
                            ->relationship('posts', 'title')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->label('Статьи блога'),
                    ]),
            ]);
    }
}
