<?php

namespace App\Filament\Resources\NotificationSettings\Schemas;

use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class NotificationSettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('Название')
                    ->maxLength(255)
                    ->placeholder('Основные настройки'),
                Section::make('Email')
                    ->schema([
                        Checkbox::make('email_enabled')
                            ->label('Включить email-уведомления'),
                        TagsInput::make('email_recipients')
                            ->label('Получатели (email)')
                            ->placeholder('email@example.com')
                            ->helperText('Введите email-адреса получателей, нажимая Enter после каждого'),
                    ]),
                Section::make('Telegram')
                    ->schema([
                        Checkbox::make('telegram_enabled')
                            ->label('Включить Telegram-уведомления'),
                        Grid::make(2)
                            ->schema([
                                TextInput::make('telegram_bot_token')
                                    ->label('Bot Token')
                                    ->password()
                                    ->revealable()
                                    ->maxLength(500),
                                TextInput::make('telegram_chat_id')
                                    ->label('Chat ID')
                                    ->maxLength(100),
                            ]),
                    ]),
            ]);
    }
}
