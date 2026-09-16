<?php

require_once __DIR__.'/api_test_helpers.php';

use App\Models\Category;

beforeEach(function () {
    prepareApiEndpointTestEnvironment();
});

afterEach(function () {
    resetApiRouteCdkDepictState();
    resetApiRouteRdkitState();
});

test('membrane categories endpoint returns membrane category tree', function () {
    $category = createApiRootCategory(Category::TYPE_MEMBRANE, 'Phospholipids');
    createApiMembrane(category: $category);

    $this->getJson(apiRoutePath('api/membrane/categories'))
        ->assertOk()
        ->assertJsonFragment([
            'title' => 'Phospholipids',
        ])
        ->assertJsonFragment([
            'name' => 'DOPC',
        ]);
});

test('membrane show endpoint returns membrane resource', function () {
    $membrane = createApiMembrane();

    $this->getJson(apiRoutePath("api/membrane/{$membrane->id}"))
        ->assertOk()
        ->assertJsonPath('data.id', $membrane->id)
        ->assertJsonPath('data.abbreviation', $membrane->abbreviation);
});

test('membrane stats endpoint returns aggregated membrane stats', function () {
    $dataset = createApiDataset();
    createApiPassiveInteraction([
        'dataset' => $dataset,
    ]);

    $this->getJson(apiRoutePath("api/membrane/{$dataset->membrane_id}/stats"))
        ->assertOk()
        ->assertJsonPath('data.total.interactions_passive', 1)
        ->assertJsonPath('data.total.structures', 1);
});
