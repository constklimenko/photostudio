<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Filament\Resources\Inquiries\InquiryResource;
use App\Filament\Resources\Projects\ProjectResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditProject extends EditRecord
{
    protected static string $resource = ProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('openInquiry')
                ->label('Открыть заявку')
                ->icon(Heroicon::OutlinedArrowRight)
                ->color('info')
                ->visible(fn () => $this->record->inquiry()->exists())
                ->url(fn () => $this->record->inquiry
                    ? InquiryResource::getUrl('edit', ['record' => $this->record->inquiry])
                    : null),
            DeleteAction::make(),
        ];
    }
}
