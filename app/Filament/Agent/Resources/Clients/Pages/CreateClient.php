<?php

namespace App\Filament\Agent\Resources\Clients\Pages;

use App\Filament\Agent\Resources\Clients\ClientResource;
use Filament\Resources\Pages\CreateRecord;

class CreateClient extends CreateRecord
{
    protected static string $resource = ClientResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
