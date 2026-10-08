<?php

namespace App\Filament\Admin\Resources\HelpTopics\Pages;

use App\Filament\Admin\Resources\HelpTopics\HelpTopicResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHelpTopics extends ListRecords
{
    protected static string $resource = HelpTopicResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
