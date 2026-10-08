<?php

use App\Support\HelpdeskSettings;

if (! function_exists('helpdesk')) {
    /**
     * Helpdesk settings: branding, contact details and behaviour configured in the admin panel.
     */
    function helpdesk(): HelpdeskSettings
    {
        return app(HelpdeskSettings::class);
    }
}
