<?php

namespace App\Filament\Admin\Resources\Teams\Pages;

use App\Filament\Admin\Resources\Teams\TeamResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTeam extends CreateRecord
{
    protected static string $resource = TeamResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
