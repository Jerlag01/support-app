<?php

namespace App\Filament\Agent\Resources\FaqCategories\Pages;

use App\Filament\Agent\Resources\FaqCategories\FaqCategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditFaqCategory extends EditRecord
{
    protected static string $resource = FaqCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
