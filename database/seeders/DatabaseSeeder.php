<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Creates the default helpdesk configuration only. Create the first admin with
     * `php artisan support:create-user`; use DemoSeeder for local demo data.
     */
    public function run(): void
    {
        $this->call(HelpdeskDefaultsSeeder::class);
    }
}
