<?php

require_once __DIR__.'/api_test_helpers.php';

use App\Models\Stats;

beforeEach(function () {
    prepareApiEndpointTestEnvironment();
});

afterEach(function () {
    resetApiRouteCdkDepictState();
    resetApiRouteRdkitState();
});

test('stats endpoint returns counts and plot containers', function () {
    Stats::query()->create([
        'type' => Stats::TYPE_COUNTS,
        'content' => [
            'total_passive_interactions' => 5,
            'total_active_interactions' => 2,
            'total_structures' => 3,
            'total_membranes' => 4,
            'total_methods' => 6,
        ],
    ]);

    $this->getJson(apiRoutePath('api/stats'))
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'total',
                'plots' => [
                    'interactionsLine',
                    'databasesBar',
                    'proteinsBar',
                ],
            ],
        ]);
});

test('publication stats summary endpoint returns publication statistics', function () {
    createApiPublication(['year' => 2023]);

    $this->getJson(apiRoutePath('api/stats/publications'))
        ->assertOk()
        ->assertJsonPath('data.total.publications', 1)
        ->assertJsonPath('data.minPublishedYear', 2023);
});
