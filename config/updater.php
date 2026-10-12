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

    // Every step (artisan, composer) runs with this PHP, whatever PHP started the
    // command. Use the version PHP-FPM serves the site with: a name found in
    // /usr/local/bin, /usr/bin or /bin, or a full path.
    'php' => env('UPDATER_PHP', 'php8.4'),

    'composer' => env('UPDATER_COMPOSER'),
    'npm' => env('UPDATER_NPM'),

    // Home, composer and npm caches for the update steps. Default: inside the app's
    // own storage folder, which is always writable, instead of the web user's home
    // folder (often a root-owned /var/www).
    'cache_path' => env('UPDATER_CACHE_PATH'),

    // Optional private key (deploy key) for an SSH remote, e.g. storage/app/updater/deploy_key.
    'ssh_key' => env('UPDATER_SSH_KEY'),
];
