<?php

namespace App\Filament\Admin\Resources\SlaPlans\Pages;

use App\Filament\Admin\Resources\SlaPlans\SlaPlanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSlaPlans extends ListRecords
{
    protected static string $resource = SlaPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
