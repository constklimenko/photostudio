<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn ($record) => $record && ! $record->is_system),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if ($this->record->is_system) {
            unset($data['slug']);
        }

        return $data;
    }
}
