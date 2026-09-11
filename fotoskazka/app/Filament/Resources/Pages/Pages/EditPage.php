<?php

namespace App\Filament\Resources\Pages\Pages;

use App\Filament\Resources\Pages\PageResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPage extends EditRecord
{
    protected static string $resource = PageResource::class;

    private const SYSTEM_SLUGS = ['home', 'services', 'portfolio', 'blog', 'video'];

    protected function getHeaderActions(): array
    {
        $actions = [];

        if (! in_array($this->record?->slug, self::SYSTEM_SLUGS, true)) {
            $actions[] = DeleteAction::make();
        }

        return $actions;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['_slug_manual'] = '1';

        return $data;
    }
}
