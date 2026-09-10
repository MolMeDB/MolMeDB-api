<?php

require_once __DIR__.'/api_test_helpers.php';

use Modules\PredictionWorkers\Models\PredictionDataset;
use Modules\PredictionWorkers\Models\PredictionMembrane;
use Modules\PredictionWorkers\Models\PredictionStructure;

beforeEach(function () {
    prepareApiEndpointTestEnvironment();
});

afterEach(function () {
    resetApiRouteCdkDepictState();
    resetApiRouteRdkitState();
});

function createApiPredictionMembrane(array $attributes = []): PredictionMembrane
{
    return PredictionMembrane::query()->create([
        'remote_id' => fake()->unique()->numberBetween(1, 100000),
        'name' => 'DOPC bilayer',
        'abbreviation' => 'DOPC',
        ...$attributes,
    ]);
}

function createApiPredictionDataset(array $attributes = []): PredictionDataset
{
    $membrane = $attributes['membrane'] ?? createApiPredictionMembrane();
    unset($attributes['membrane']);

    return PredictionDataset::query()->create([
        'temperature' => 25.0,
        'membrane_id' => $membrane->id,
        'method_type' => 'membrane_water',
        ...$attributes,
    ]);
}

test('predictions datasets endpoint requires authentication', function () {
    $this->getJson(apiRoutePath('api/predictions/datasets'))
        ->assertUnauthorized();
});

test('predictions dataset detail endpoint requires authentication', function () {
    $dataset = createApiPredictionDataset();

    // authorizeDatasetAccess() aborts 403 (not 401) when there's no token
    // and no authenticated user — it's a manual in-controller check, not
    // the auth:sanctum middleware.
    $this->getJson(apiRoutePath("api/predictions/datasets/{$dataset->id}"))
        ->assertForbidden();
});

test('predictions dataset records endpoint requires authentication', function () {
    $dataset = createApiPredictionDataset();

    $this->getJson(apiRoutePath("api/predictions/datasets/{$dataset->id}/records"))
        ->assertForbidden();
});

test('predictions dataset structures endpoint requires authentication', function () {
    $dataset = createApiPredictionDataset();

    $this->getJson(apiRoutePath("api/predictions/datasets/{$dataset->id}/structures"))
        ->assertForbidden();
});

test('predictions by structure endpoint requires authentication', function () {
    $structure = PredictionStructure::query()->create([]);

    $this->getJson(apiRoutePath("api/predictions/byStructure/{$structure->id}"))
        ->assertForbidden();
});
