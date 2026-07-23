<?php

namespace App\Filament\Resources\FaqItems;

use App\Filament\Resources\FaqItems\Pages\CreateFaqItem;
use App\Filament\Resources\FaqItems\Pages\EditFaqItem;
use App\Filament\Resources\FaqItems\Pages\ListFaqItems;
use App\Filament\Resources\FaqItems\Schemas\FaqItemForm;
use App\Filament\Resources\FaqItems\Tables\FaqItemsTable;
use App\Models\FaqItem;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class FaqItemResource extends Resource
{
    protected static ?string $model = FaqItem::class;

    protected static ?string $navigationLabel = 'FAQ';

    protected static ?string $modelLabel = 'вопрос';

    protected static ?string $pluralModelLabel = 'FAQ';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-question-mark-circle';

    protected static ?int $navigationSort = 9;

    protected static \UnitEnum|string|null $navigationGroup = 'Контент';

    public static function form(Schema $schema): Schema
    {
        return FaqItemForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FaqItemsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFaqItems::route('/'),
            'create' => CreateFaqItem::route('/create'),
            'edit' => EditFaqItem::route('/{record}/edit'),
        ];
    }
}
