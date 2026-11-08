<?php

namespace App\Filament\Resources\ArTargets\Pages;

use App\Filament\Resources\ArTargets\ArTargetResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListArTargets extends ListRecords
{
    protected static string $resource = ArTargetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
