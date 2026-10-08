<?php

namespace App\Filament\Admin\Resources\HelpTopics\Pages;

use App\Filament\Admin\Resources\HelpTopics\HelpTopicResource;
use Filament\Resources\Pages\CreateRecord;

class CreateHelpTopic extends CreateRecord
{
    protected static string $resource = HelpTopicResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
