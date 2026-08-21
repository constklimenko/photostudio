<?php

namespace App\Filament\Resources\Photos;

use App\Filament\Resources\Photos\Pages\CreatePhoto;
use App\Filament\Resources\Photos\Pages\EditPhoto;
use App\Filament\Resources\Photos\Pages\ListPhotos;
use App\Filament\Resources\Photos\Schemas\PhotoForm;
use App\Filament\Resources\Photos\Tables\PhotosTable;
use App\Models\Photo;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PhotoResource extends Resource
{
    protected static ?string $model = Photo::class;

    protected static ?string $navigationLabel = 'Фотографии';

    protected static ?string $modelLabel = 'фотографию';

    protected static ?string $pluralModelLabel = 'Фотографии';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCamera;

    protected static ?int $navigationSort = 4;

    protected static \UnitEnum|string|null $navigationGroup = 'Контент';

    public static function form(Schema $schema): Schema
    {
        return PhotoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PhotosTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPhotos::route('/'),
            'create' => CreatePhoto::route('/create'),
            'edit' => EditPhoto::route('/{record}/edit'),
        ];
    }
}
