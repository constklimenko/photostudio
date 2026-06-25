<?php

namespace App\Filament\Resources\Albums\Pages;

use App\Filament\Resources\Albums\AlbumResource;
use App\Models\Album;
use App\Models\Media;
use App\Models\Photo;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\DB;

class UploadPhotos extends Page
{
    protected static string $resource = AlbumResource::class;

    protected string $view = 'filament.resources.albums.pages.upload-photos';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->schema([
                        TextInput::make('title')
                            ->label('Название альбома')
                            ->required()
                            ->maxLength(255),
                        Select::make('type')
                            ->label('Тип альбома')
                            ->required()
                            ->default('portfolio')
                            ->options([
                                'portfolio' => 'Портфолио',
                                'project' => 'Проект (съёмка)',
                                'homepage' => 'Главная страница',
                                'service' => 'Услуга',
                                'client' => 'Клиентская галерея',
                            ])
                            ->live(),
                        Textarea::make('description')
                            ->label('Описание'),
                        Select::make('project_id')
                            ->label('Проект')
                            ->relationship('project', 'title')
                            ->preload()
                            ->searchable()
                            ->nullable()
                            ->hidden(fn ($get) => $get('type') !== 'project'),
                        FileUpload::make('cover')
                            ->label('Обложка')
                            ->image()
                            ->disk('public')
                            ->visibility('public')
                            ->nullable(),
                    ]),
                FileUpload::make('photos')
                    ->label('Фотографии')
                    ->required()
                    ->multiple()
                    ->image()
                    ->minFiles(1)
                    ->maxFiles(500)
                    ->disk('public')
                    ->visibility('public')
                    ->panelLayout('grid')
                    ->previewable(true)
                    ->reorderable()
                    ->appendFiles()
                    ->maxSize(51200),
            ])
            ->statePath('data');
    }

    public function create(): void
    {
        $data = $this->form->getState();

        DB::transaction(function () use ($data) {
            $album = Album::create([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'type' => $data['type'] ?? 'portfolio',
                'project_id' => $data['type'] === 'project' ? ($data['project_id'] ?? null) : null,
                'slug' => str($data['title'])->slug()->append('-'.now()->format('YmdHis')),
            ]);

            if (! empty($data['cover'])) {
                $coverMedia = Media::create([
                    'file_path' => $data['cover'],
                    'disk' => 'public',
                    'collection' => 'covers',
                    'title' => $album->title,
                ]);
                $album->update(['cover_media_id' => $coverMedia->id]);
            }

            foreach ($data['photos'] as $index => $photoPath) {
                $media = Media::create([
                    'file_path' => $photoPath,
                    'disk' => 'public',
                    'collection' => 'gallery',
                    'title' => $album->title.' — '.($index + 1),
                ]);

                Photo::create([
                    'album_id' => $album->id,
                    'media_id' => $media->id,
                    'sort_order' => $index,
                ]);
            }
        });

        $this->redirect(AlbumResource::getUrl('index'));
    }
}
