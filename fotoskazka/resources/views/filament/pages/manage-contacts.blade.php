<x-filament-panels::page>
    <form wire:submit="save" class="fi-form">
        {{ $this->form }}

        <div class="flex justify-end mt-6">
            <x-filament::button
                type="submit"
                color="primary"
            >
                Сохранить
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
