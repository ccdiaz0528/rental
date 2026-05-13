<?php

namespace App\Filament\Resources\User\Pages;

use App\Filament\Resources\User\UserResource;
use Filament\Resources\Pages\ListRecords;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;
}
