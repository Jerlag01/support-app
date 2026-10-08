<?php

namespace App\Filament\Agent\Resources\FaqCategories\Pages;

use App\Filament\Agent\Resources\FaqCategories\FaqCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFaqCategories extends ListRecords
{
    protected static string $resource = FaqCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
