<?php

namespace App\Filament\Admin\Resources\HelpTopics\Pages;

use App\Filament\Admin\Resources\HelpTopics\HelpTopicResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditHelpTopic extends EditRecord
{
    protected static string $resource = HelpTopicResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
