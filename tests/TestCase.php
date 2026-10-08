<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Refuse to run against anything but a test database: RefreshDatabase wipes it.
     */
    public function createApplication()
    {
        $app = parent::createApplication();

        $database = (string) $app['config']->get('database.connections.'.$app['config']->get('database.default').'.database');

        if (! str_ends_with($database, '_test') && $database !== ':memory:') {
            throw new RuntimeException("Tests must run against a *_test database, not [{$database}]. Run them with phpunit.xml.");
        }

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Symfony's fake requests default to "en-us"; browse like the Dutch-speaking majority of clients.
        $this->withHeader('Accept-Language', 'nl-BE,nl;q=0.9');
    }
}
