<?php

namespace App\Filament\Agent\Resources\Faqs\Pages;

use App\Filament\Agent\Resources\Faqs\FaqResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFaq extends CreateRecord
{
    protected static string $resource = FaqResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
