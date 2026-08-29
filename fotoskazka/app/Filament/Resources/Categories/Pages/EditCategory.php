<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Filament\Resources\Categories\CategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCategory extends EditRecord
{
    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->requiresConfirmation()
                ->disabled(fn (): bool => ! $this->record->canBeDeleted())
                ->tooltip(fn (): ?string => $this->record->canBeDeleted()
                    ? null
                    : 'Нельзя удалить: сначала переместите или удалите дочерние категории и услуги'),
        ];
    }
}
