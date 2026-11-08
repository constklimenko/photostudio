<?php

namespace App\Filament\Pages;

use App\Models\SiteSetting;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ManageContacts extends Page
{
    protected static ?string $navigationLabel = 'Контакты';

    protected static ?string $modelLabel = 'контакты';

    protected static ?string $pluralModelLabel = 'Контакты';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhone;

    protected static ?int $navigationSort = 11;

    protected static string|\UnitEnum|null $navigationGroup = 'Администрирование';

    protected string $view = 'filament.pages.manage-contacts';

    protected static ?string $slug = 'contacts';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'phone' => SiteSetting::get('contacts.phone', ''),
            'email' => SiteSetting::get('contacts.email', ''),
            'address' => SiteSetting::get('contacts.address', ''),
            'city' => SiteSetting::get('contacts.city', ''),
        ]);
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Контактные данные')
                    ->description('Отображаются в шапке, футере и мобильной панели на всех страницах сайта. Пустое поле — используется значение из настроек окружения.')
                    ->schema([
                        TextInput::make('phone')
                            ->label('Телефон')
                            ->maxLength(255)
                            ->placeholder('+7 (999) 123-45-67'),
                        TextInput::make('email')
                            ->label('Email для связи')
                            ->email()
                            ->maxLength(255),
                    ]),
                Section::make('Адрес студии')
                    ->description('Используется в schema.org LocalBusiness для локального SEO.')
                    ->schema([
                        TextInput::make('city')
                            ->label('Город')
                            ->maxLength(255)
                            ->placeholder('Уфа'),
                        TextInput::make('address')
                            ->label('Адрес (улица)')
                            ->maxLength(255)
                            ->placeholder('ул. 50 лет СССР'),
                    ]),
            ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        SiteSetting::set('contacts.phone', $data['phone'] ?? null);
        SiteSetting::set('contacts.email', $data['email'] ?? null);
        SiteSetting::set('contacts.address', $data['address'] ?? null);
        SiteSetting::set('contacts.city', $data['city'] ?? null);

        Notification::make()
            ->success()
            ->title('Контакты сохранены')
            ->send();
    }
}
