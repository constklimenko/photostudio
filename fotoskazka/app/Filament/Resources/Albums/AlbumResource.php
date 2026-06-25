<?php

namespace App\Filament\Resources\Albums;

use App\Filament\Resources\Albums\Pages\CreateAlbum;
use App\Filament\Resources\Albums\Pages\EditAlbum;
use App\Filament\Resources\Albums\Pages\ListAlbums;
use App\Filament\Resources\Albums\Pages\UploadPhotos;
use App\Filament\Resources\Albums\RelationManagers\PhotosRelationManager;
use App\Filament\Resources\Albums\Schemas\AlbumForm;
use App\Filament\Resources\Albums\Tables\AlbumsTable;
use App\Models\Album;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AlbumResource extends Resource
{
    protected static ?string $model = Album::class;

    protected static ?string $navigationLabel = 'Альбомы';

    protected static ?string $modelLabel = 'альбом';

    protected static ?string $pluralModelLabel = 'Альбомы';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static ?int $navigationSort = 3;

    protected static \UnitEnum|string|null $navigationGroup = 'Контент';

    public static function form(Schema $schema): Schema
    {
        return AlbumForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AlbumsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            PhotosRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAlbums::route('/'),
            'create' => CreateAlbum::route('/create'),
            'edit' => EditAlbum::route('/{record}/edit'),
            'upload' => UploadPhotos::route('/upload'),
        ];
    }
}
