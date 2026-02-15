<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Use collection driver for Scout during tests (in-memory)
        if (class_exists(\Laravel\Scout\Scout::class)) {
            config(['scout.driver' => 'collection']);
        }
    }
}
