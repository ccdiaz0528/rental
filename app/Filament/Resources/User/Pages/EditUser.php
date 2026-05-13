<?php

namespace App\Filament\Resources\User\Pages;

use App\Filament\Resources\User\UserResource;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;
}
