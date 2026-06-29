<?php

namespace App\Filament\Resources\ServiceItems;

use App\Filament\Resources\ServiceItems\Pages\CreateServiceItem;
use App\Filament\Resources\ServiceItems\Pages\EditServiceItem;
use App\Filament\Resources\ServiceItems\Pages\ListServiceItems;
use App\Filament\Resources\ServiceItems\Schemas\ServiceItemForm;
use App\Filament\Resources\ServiceItems\Tables\ServiceItemsTable;
use App\Models\ServiceItem;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ServiceItemResource extends Resource
{
    protected static ?string $model = ServiceItem::class;

    protected static ?string $navigationLabel = 'Пункты услуг';

    protected static ?string $modelLabel = 'пункт услуги';

    protected static ?string $pluralModelLabel = 'Пункты услуг';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedListBullet;

    protected static ?int $navigationSort = 2;

    protected static \UnitEnum|string|null $navigationGroup = 'Контент';

    public static function form(Schema $schema): Schema
    {
        return ServiceItemForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ServiceItemsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListServiceItems::route('/'),
            'create' => CreateServiceItem::route('/create'),
            'edit' => EditServiceItem::route('/{record}/edit'),
        ];
    }
}
