<x-filament-panels::page>
    <div class="flex justify-end mb-4">
        <x-filament::button
            wire:click="refreshFolders"
            icon="heroicon-m-arrow-path"
            color="gray"
            size="sm"
        >
            Обновить список папок
        </x-filament::button>
    </div>

    {{ $this->form }}

    <div class="flex justify-end mt-6">
        <x-filament::button
            wire:click="create"
            icon="heroicon-m-arrow-down-tray"
            color="primary"
            size="lg"
        >
            Импортировать альбом
        </x-filament::button>
    </div>
</x-filament-panels::page>
