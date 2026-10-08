<?php

namespace App\Providers;

use App\Support\HelpdeskSettings;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(HelpdeskSettings::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->app->afterResolving('mail.manager', fn () => $this->app->make(HelpdeskSettings::class)->applyMailConfig());
    }
}
