<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Update from git
    |--------------------------------------------------------------------------
    |
    | The admin page "System update" queues an update; the scheduler (cron as
    | www-data) runs it, because PHP-FPM may not write the code directory.
    | Leave branch empty to follow the branch that is checked out.
    |
    */

    'remote' => env('UPDATER_REMOTE', 'origin'),
    'branch' => env('UPDATER_BRANCH'),
    'composer' => env('UPDATER_COMPOSER'),
    'npm' => env('UPDATER_NPM'),
];
