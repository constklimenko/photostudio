<?php

namespace App\Filament\Resources\ArTargets;

use App\Filament\Resources\ArTargets\Pages\CreateArTarget;
use App\Filament\Resources\ArTargets\Pages\EditArTarget;
use App\Filament\Resources\ArTargets\Pages\ListArTargets;
use App\Filament\Resources\ArTargets\RelationManagers\ContentsRelationManager;
use App\Filament\Resources\ArTargets\Schemas\ArTargetForm;
use App\Filament\Resources\ArTargets\Tables\ArTargetsTable;
use App\Models\ArTarget;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ArTargetResource extends Resource
{
    protected static ?string $model = ArTarget::class;

    protected static ?string $navigationLabel = 'Оживающие фото';

    protected static ?string $modelLabel = 'оживающее фото';

    protected static ?string $pluralModelLabel = 'Оживающие фото';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static ?int $navigationSort = 3;

    protected static \UnitEnum|string|null $navigationGroup = 'Контент';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return ArTargetForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ArTargetsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ContentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListArTargets::route('/'),
            'create' => CreateArTarget::route('/create'),
            'edit' => EditArTarget::route('/{record}/edit'),
        ];
    }
}
