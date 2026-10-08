<?php

namespace App\Filament\Admin\Resources\FooterLinks\Pages;

use App\Filament\Admin\Resources\FooterLinks\FooterLinkResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditFooterLink extends EditRecord
{
    protected static string $resource = FooterLinkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
