<?php

namespace App\Filament\Resources\SocialLinks\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class SocialLinkForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->label('Название'),
                        Select::make('icon')
                            ->required()
                            ->options([
                                'instagram' => 'Instagram',
                                'telegram' => 'Telegram',
                                'whatsapp' => 'WhatsApp',
                                'vkontakte' => 'VK',
                                'youtube' => 'YouTube',
                                'viber' => 'Viber',
                                'odnoklassniki' => 'Odnoklassniki',
                                'dzen' => 'Dzen',
                                'rutube' => 'Rutube',
                            ])
                            ->label('Иконка'),
                        TextInput::make('url')
                            ->required()
                            ->url()
                            ->maxLength(1000)
                            ->label('Ссылка'),
                        Toggle::make('is_active')
                            ->default(true)
                            ->label('Активно'),
                        TextInput::make('sort_order')
                            ->integer()
                            ->default(0)
                            ->label('Порядок'),
                    ]),
            ]);
    }
}
