<?php

namespace App\Filament\Resources\Icons;

use App\Filament\Resources\Icons\Pages\CreateIcon;
use App\Filament\Resources\Icons\Pages\EditIcon;
use App\Filament\Resources\Icons\Pages\ListIcons;
use App\Filament\Resources\Icons\Schemas\IconForm;
use App\Filament\Resources\Icons\Tables\IconsTable;
use App\Models\Icon;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class IconResource extends Resource
{
    protected static ?string $model = Icon::class;

    protected static ?string $navigationLabel = 'Иконки';

    protected static ?string $modelLabel = 'иконка';

    protected static ?string $pluralModelLabel = 'Иконки';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static ?int $navigationSort = 3;

    protected static \UnitEnum|string|null $navigationGroup = 'Контент';

    public static function form(Schema $schema): Schema
    {
        return IconForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return IconsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListIcons::route('/'),
            'create' => CreateIcon::route('/create'),
            'edit' => EditIcon::route('/{record}/edit'),
        ];
    }
}
