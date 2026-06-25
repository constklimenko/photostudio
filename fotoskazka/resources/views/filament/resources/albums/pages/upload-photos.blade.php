<x-filament-panels::page>
    {{ $this->form }}

    <div class="flex justify-end mt-6">
        <x-filament::button
            wire:click="create"
            icon="heroicon-m-cloud-arrow-up"
            color="primary"
            size="lg"
        >
            Создать альбом
        </x-filament::button>
    </div>
</x-filament-panels::page>
