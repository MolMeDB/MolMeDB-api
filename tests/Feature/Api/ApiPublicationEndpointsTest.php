<?php

require_once __DIR__.'/api_test_helpers.php';

beforeEach(function () {
    prepareApiEndpointTestEnvironment();
});

afterEach(function () {
    resetApiRouteCdkDepictState();
    resetApiRouteRdkitState();
});

test('publication index endpoint returns paginated publications', function () {
    $publication = createApiPublication([
        'title' => 'Publication index record',
    ]);

    $this->getJson(apiRoutePath('api/publication'))
        ->assertOk()
        ->assertJsonFragment([
            'title' => $publication->title,
        ]);
});

test('publication show endpoint returns publication resource', function () {
    $publication = createApiPublication();

    $this->getJson(apiRoutePath("api/publication/{$publication->id}"))
        ->assertOk()
        ->assertJsonPath('data.id', $publication->id)
        ->assertJsonPath('data.title', $publication->title);
});

test('publication stats endpoint returns publication resource with counts', function () {
    $publication = createApiPublication();
    $dataset = createApiDataset([
        'publication' => $publication,
    ]);
    createApiActiveInteraction([
        'dataset' => $dataset,
        'publication' => $publication,
    ]);
    createApiPassiveInteraction([
        'dataset' => $dataset,
        'publication' => $publication,
    ]);

    $this->getJson(apiRoutePath("api/publication/{$publication->id}/stats"))
        ->assertOk()
        ->assertJsonPath('data.id', $publication->id);
});
