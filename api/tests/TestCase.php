<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Disable API key auth in tests — the middleware is a no-op when API_KEY is empty.
        putenv('API_KEY=');
        $_ENV['API_KEY']    = '';
        $_SERVER['API_KEY'] = '';
    }
}
