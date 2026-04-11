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

test('protein categories endpoint returns protein category tree', function () {
    $category = createApiRootCategory(Category::TYPE_PROTEIN, 'Transport proteins');
    createApiProtein(category: $category);

    $this->getJson(apiRoutePath('api/protein/categories'))
        ->assertOk()
        ->assertJsonFragment([
            'title' => 'Transport proteins',
        ])
        ->assertJsonFragment([
            'name' => 'P12345',
        ]);
});

test('protein show endpoint returns protein resource', function () {
    $protein = createApiProtein();

    $this->getJson(apiRoutePath("api/protein/{$protein->id}"))
        ->assertOk()
        ->assertJsonPath('data.id', $protein->id)
        ->assertJsonPath('data.uniprot_id', $protein->uniprot_id);
});

test('protein download interactions endpoint returns csv file', function () {
    $protein = createApiProtein(['uniprot_id' => 'Q99999']);
    createApiActiveInteraction([
        'protein' => $protein,
    ]);

    $response = $this->get(apiRoutePath("api/protein/{$protein->id}/download/interactions"));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('text/plain; charset=utf-8');
});

test('protein stats endpoint returns aggregated protein stats', function () {
    $protein = createApiProtein();
    createApiActiveInteraction([
        'protein' => $protein,
    ]);

    $this->getJson(apiRoutePath("api/protein/{$protein->id}/stats"))
        ->assertOk()
        ->assertJsonPath('data.interactions_count', 1)
        ->assertJsonPath('data.structures_count', 1);
});
