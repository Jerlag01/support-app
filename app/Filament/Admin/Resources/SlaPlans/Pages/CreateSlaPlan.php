<?php

namespace App\Filament\Admin\Resources\SlaPlans\Pages;

use App\Filament\Admin\Resources\SlaPlans\SlaPlanResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSlaPlan extends CreateRecord
{
    protected static string $resource = SlaPlanResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
