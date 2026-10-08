<?php

namespace App\Filament\Admin\Resources\Employees\Pages;

use App\Filament\Admin\Resources\Employees\EmployeeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListEmployees extends ListRecords
{
    protected static string $resource = EmployeeResource::class;

    /**
     * Reminds admins when the page is switched off, so they know why it is not in the portal.
     */
    public function getSubheading(): string|Htmlable|null
    {
        return helpdesk()->get('show_who_is_who') ? null : __('admin.help.who_is_who_disabled');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
