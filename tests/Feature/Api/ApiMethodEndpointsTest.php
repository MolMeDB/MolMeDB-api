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

test('method categories endpoint returns method category tree', function () {
    $category = createApiRootCategory(Category::TYPE_METHOD, 'Transport methods');
    createApiMethod(category: $category);

    $this->getJson(apiRoutePath('api/method/categories'))
        ->assertOk()
        ->assertJsonFragment([
            'title' => 'Transport methods',
        ])
        ->assertJsonFragment([
            'name' => 'PAMPA',
        ]);
});

test('method show endpoint returns method resource', function () {
    $method = createApiMethod();

    $this->getJson(apiRoutePath("api/method/{$method->id}"))
        ->assertOk()
        ->assertJsonPath('data.id', $method->id)
        ->assertJsonPath('data.abbreviation', $method->abbreviation);
});

test('method stats endpoint returns aggregated method stats', function () {
    $dataset = createApiDataset();
    createApiPassiveInteraction([
        'dataset' => $dataset,
    ]);

    $this->getJson(apiRoutePath("api/method/{$dataset->method_id}/stats"))
        ->assertOk()
        ->assertJsonPath('data.total.interactions_passive', 1)
        ->assertJsonPath('data.total.structures', 1);
});
