<?php

namespace App\Filament\Resources\Inquiries\Pages;

use App\Actions\Inquiry\CreateProjectFromInquiry;
use App\Filament\Resources\Inquiries\InquiryResource;
use App\Filament\Resources\Projects\ProjectResource;
use App\Jobs\SendInquiryNotifications;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditInquiry extends EditRecord
{
    protected static string $resource = InquiryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createProject')
                ->label('Создать проект')
                ->icon(Heroicon::OutlinedPlus)
                ->visible(fn () => $this->record->project_id === null)
                ->form([
                    TextInput::make('title')
                        ->label('Название проекта')
                        ->required()
                        ->maxLength(255),
                    Select::make('type')
                        ->label('Тип проекта')
                        ->required()
                        ->options([
                            'individual' => 'Individual',
                            'family' => 'Family',
                            'event' => 'Event',
                            'wedding' => 'Wedding',
                            'school' => 'School',
                            'kindergarten' => 'Kindergarten',
                        ]),
                    Select::make('manager_id')
                        ->label('Менеджер')
                        ->options(fn () => User::pluck('name', 'id'))
                        ->searchable()
                        ->nullable(),
                    Select::make('client_id')
                        ->label('Клиент')
                        ->options(fn () => User::pluck('name', 'id'))
                        ->searchable()
                        ->nullable()
                        ->default(fn () => $this->record->user_id),
                    DatePicker::make('shooting_date')
                        ->label('Дата съёмки')
                        ->default(fn () => $this->record->shooting_date),
                ])
                ->action(function (array $data) {
                    $project = app(CreateProjectFromInquiry::class)->execute($this->record, $data);

                    Notification::make()
                        ->title('Проект создан')
                        ->body("Проект «{$project->title}» создан из заявки №{$this->record->id}")
                        ->success()
                        ->send();

                    $this->redirect(ProjectResource::getUrl('edit', ['record' => $project]));
                }),
            Action::make('openProject')
                ->label('Открыть проект')
                ->icon(Heroicon::OutlinedArrowRight)
                ->color('success')
                ->visible(fn () => $this->record->project_id !== null)
                ->url(fn () => $this->record->project
                    ? ProjectResource::getUrl('edit', ['record' => $this->record->project])
                    : null),
            DeleteAction::make(),
            Action::make('resendNotifications')
                ->label('Отправить повторно')
                ->icon(Heroicon::OutlinedPaperAirplane)
                ->color('warning')
                ->visible(fn () => filled($this->record->notification_error))
                ->action(function () {
                    $this->record->update(['notification_error' => null]);
                    dispatch(new SendInquiryNotifications($this->record));

                    Notification::make()
                        ->title('Уведомления поставлены в очередь')
                        ->success()
                        ->send();
                }),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return $data;
    }
}
