<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    /** Abort before RefreshDatabase can ever issue SQL against a real database. */
    protected function beforeRefreshingDatabase()
    {
        if (! $this->app->environment('testing')
            || config('database.default') !== 'sqlite'
            || config('database.connections.sqlite.database') !== ':memory:') {
            throw new \RuntimeException('Tests require APP_ENV=testing and SQLite :memory:; external databases are forbidden.');
        }
    }

    protected function csrf(): string
    {
        $token = bin2hex(random_bytes(20));
        $this->withSession(['_token' => $token]);

        return $token;
    }
}
