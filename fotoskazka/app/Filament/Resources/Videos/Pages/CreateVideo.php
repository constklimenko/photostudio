<?php

namespace App\Filament\Resources\Videos\Pages;

use App\Filament\Resources\Videos\VideosResource;
use Filament\Resources\Pages\CreateRecord;

class CreateVideo extends CreateRecord
{
    protected static string $resource = VideosResource::class;
}
