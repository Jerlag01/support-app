<?php

namespace App\Providers\Filament;

use App\Enums\AdminNavigationGroup;
use App\Providers\Filament\Concerns\ConfiguresSupportPanel;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;

/**
 * Helpdesk configuration, admins only (osTicket's Admin Panel).
 */
class AdminPanelProvider extends PanelProvider
{
    use ConfiguresSupportPanel;

    public function panel(Panel $panel): Panel
    {
        return $this->configureSupportPanel($panel, 'Admin')
            ->id('admin')
            ->path('admin')
            ->navigationGroups(AdminNavigationGroup::navigationGroups([
                AdminNavigationGroup::Helpdesk,
                AdminNavigationGroup::Agents,
                AdminNavigationGroup::System,
            ]))
            ->discoverResources(in: app_path('Filament/Admin/Resources'), for: 'App\Filament\Admin\Resources')
            ->discoverPages(in: app_path('Filament/Admin/Pages'), for: 'App\Filament\Admin\Pages')
            ->discoverWidgets(in: app_path('Filament/Admin/Widgets'), for: 'App\Filament\Admin\Widgets')
            ->pages([
                Dashboard::class,
            ]);
    }
}
