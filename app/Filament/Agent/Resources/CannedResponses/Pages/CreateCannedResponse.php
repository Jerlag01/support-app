<?php

namespace App\Filament\Agent\Resources\CannedResponses\Pages;

use App\Filament\Agent\Resources\CannedResponses\CannedResponseResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCannedResponse extends CreateRecord
{
    protected static string $resource = CannedResponseResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
