<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\Filament\AgentPanelProvider;

return [
    AppServiceProvider::class,
    AgentPanelProvider::class,
    AdminPanelProvider::class,
];
