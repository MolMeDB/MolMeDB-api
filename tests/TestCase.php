<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Also wrap the "predictions" connection in a per-test rollback
     * transaction (RefreshDatabase otherwise only does this for the
     * default connection) — its schema is migrated once in
     * tests/Pest.php, so this is what gives each test a clean slate.
     */
    protected $connectionsToTransact = [null, 'predictions'];
}
