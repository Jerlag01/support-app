<?php

namespace App\Providers\Filament;

use App\Enums\AdminNavigationGroup;
use App\Providers\Filament\Concerns\ConfiguresSupportPanel;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;

/**
 * Day-to-day ticket work for all agents (osTicket's Agent Panel / "scp").
 */
class AgentPanelProvider extends PanelProvider
{
    use ConfiguresSupportPanel;

    public function panel(Panel $panel): Panel
    {
        return $this->configureSupportPanel($panel, 'Agent')
            ->default()
            ->id('agent')
            ->path('agent')
            ->navigationGroups(AdminNavigationGroup::navigationGroups([
                AdminNavigationGroup::Tickets,
                AdminNavigationGroup::Users,
                AdminNavigationGroup::KnowledgeBase,
            ]))
            ->discoverResources(in: app_path('Filament/Agent/Resources'), for: 'App\Filament\Agent\Resources')
            ->discoverPages(in: app_path('Filament/Agent/Pages'), for: 'App\Filament\Agent\Pages')
            ->discoverWidgets(in: app_path('Filament/Agent/Widgets'), for: 'App\Filament\Agent\Widgets')
            ->pages([
                Dashboard::class,
            ]);
    }
}
