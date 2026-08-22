<?php

namespace App\Filament\Resources\Albums\Pages;

use App\Filament\Resources\Albums\AlbumResource;
use App\Jobs\ImportAlbumFromYandexDisk;
use Closure;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ImportFromYandexDisk extends Page
{
    protected static string $resource = AlbumResource::class;

    protected string $view = 'filament.resources.albums.pages.import-from-yandex-disk';

    protected const FOLDER_CACHE_TTL_MINUTES = 10;

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
                        Select::make('folder_top')
                            ->label('Папка на Яндекс.Диске')
                            ->options(fn (): array => $this->getYandexDiskFolders())
                            ->searchable()
                            ->preload()
                            ->placeholder('Выберите папку')
                            ->live()
                            ->afterStateUpdated(fn ($set) => $set('folder_sub', null))
                            ->requiredWithout('folder_manual')
                            ->visible(fn ($get) => ! $get('manual_folder')),
                        Select::make('folder_sub')
                            ->label('Подпапка (необязательно)')
                            ->options(
                                fn ($get): array => filled($get('folder_top'))
                                    ? $this->getYandexDiskFolders((string) $get('folder_top'))
                                    : []
                            )
                            ->searchable()
                            ->placeholder('— импортировать выбранную папку целиком —')
                            ->nullable()
                            ->visible(fn ($get) => ! $get('manual_folder') && filled($get('folder_top'))),
                        Toggle::make('manual_folder')
                            ->label('Указать путь вручную')
                            ->helperText('Для папок, которых нет в списке (глубже двух уровней)')
                            ->live()
                            ->default(false)
                            ->dehydrated(false)
                            ->columnSpanFull(),
                        TextInput::make('folder_manual')
                            ->label('Путь к папке на Яндекс.Диске')
                            ->helperText('Относительно корневой директории диска, например: 2025/vypusknoy-11a')
                            ->requiredWithout('folder_top')
                            ->maxLength(1000)
                            ->visible(fn ($get) => (bool) $get('manual_folder'))
                            ->rule($this->folderExistsRule())
                            ->columnSpanFull(),
                        Select::make('project_id')
                            ->label('Проект')
                            ->relationship('project', 'title')
                            ->preload()
                            ->searchable()
                            ->nullable()
                            ->hidden(fn ($get) => $get('type') !== 'project'),
                        Textarea::make('description')
                            ->label('Описание')
                            ->columnSpanFull(),
                        Toggle::make('use_first_as_cover')
                            ->label('Использовать первое фото как обложку')
                            ->default(true)
                            ->columnSpanFull(),
                    ]),
            ])
            ->statePath('data');
    }

    protected function folderExistsRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $folder = trim((string) $value, '/');

            if ($folder === '' || ! Storage::disk('yandex_disk')->directoryExists($folder)) {
                $fail("Папка [{$folder}] не найдена на Яндекс.Диске.");
            }
        };
    }

    /**
     * Список папок на Яндекс.Диске (относительно корневой директории диска).
     * Неглубокий листинг одного уровня — один запрос к API; результат кэшируется.
     *
     * @return array<string, string>
     */
    protected function getYandexDiskFolders(string $parent = ''): array
    {
        $cacheKey = $this->foldersCacheKey($parent);

        $cached = Cache::get($cacheKey);

        if (is_array($cached)) {
            return $cached;
        }

        try {
            $directories = Storage::disk('yandex_disk')->directories($parent);
        } catch (Throwable) {
            return [];
        }

        $folders = collect($directories)
            ->filter(fn (string $path): bool => $path !== ''
                && $path !== '.'
                && ! str_starts_with(basename($path), '.'))
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->mapWithKeys(fn (string $path): array => [$path => $path])
            ->all();

        Cache::put($cacheKey, $folders, now()->addMinutes(self::FOLDER_CACHE_TTL_MINUTES));

        return $folders;
    }

    protected function foldersCacheKey(string $parent = ''): string
    {
        return 'yandex_disk_dirs:'.md5($parent);
    }

    public function refreshFolders(): void
    {
        Cache::forget($this->foldersCacheKey(''));
        Cache::forget($this->foldersCacheKey((string) ($this->data['folder_top'] ?? '')));
    }

    public function create(): void
    {
        $this->form->validate();
        $data = $this->form->getState();

        try {
            $data['folder'] = $this->resolveFolder($data);
        } catch (Throwable) {
            Notification::make()
                ->danger()
                ->title('Ошибка импорта')
                ->body('Укажите существующую папку на Яндекс.Диске.')
                ->send();

            return;
        }

        ImportAlbumFromYandexDisk::dispatch($data);

        Notification::make()
            ->success()
            ->title('Импорт запущен')
            ->body('Альбом создаётся в фоне; обработка фотографий выполняется очередью. Обновите список альбомов через минуту.')
            ->send();

        $this->redirect(AlbumResource::getUrl('index'));
    }

    protected function resolveFolder(array $data): string
    {
        foreach (['folder_manual', 'folder_sub', 'folder_top'] as $field) {
            $folder = trim((string) ($data[$field] ?? ''), '/');

            if ($folder !== '') {
                return $folder;
            }
        }

        throw new \RuntimeException('Папка не выбрана.');
    }
}
