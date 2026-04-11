<?php

require_once __DIR__.'/api_test_helpers.php';

beforeEach(function () {
    prepareApiEndpointTestEnvironment();
});

afterEach(function () {
    resetApiRouteCdkDepictState();
    resetApiRouteRdkitState();
});

test('search structures endpoint returns matching structure records', function () {
    $structure = createApiStructure([
        'identifier' => 'MM2001',
    ]);

    $this->getJson(apiRoutePath('api/search/structures'))
        ->assertOk()
        ->assertJsonFragment([
            'title' => $structure->identifier,
        ]);
});

test('search membranes endpoint returns matching membrane records', function () {
    $membrane = createApiMembrane([
        'abbreviation' => 'DMPC',
    ]);

    $this->getJson(apiRoutePath('api/search/membranes'))
        ->assertOk()
        ->assertJsonFragment([
            'title' => $membrane->abbreviation,
        ]);
});

test('search methods endpoint returns matching method records', function () {
    $method = createApiMethod([
        'abbreviation' => 'IAM',
    ]);

    $this->getJson(apiRoutePath('api/search/methods'))
        ->assertOk()
        ->assertJsonFragment([
            'title' => $method->abbreviation,
        ]);
});

test('search proteins endpoint returns matching protein records', function () {
    $protein = createApiProtein([
        'uniprot_id' => 'Q8TEST',
    ]);

    $this->getJson(apiRoutePath('api/search/proteins'))
        ->assertOk()
        ->assertJsonFragment([
            'title' => $protein->uniprot_id,
        ]);
});

test('search datasets endpoint returns publication search records', function () {
    $publication = createApiPublication([
        'title' => 'Dataset publication result',
        'citation' => 'Dataset citation',
    ]);

    $this->getJson(apiRoutePath('api/search/datasets'))
        ->assertOk()
        ->assertJsonFragment([
            'title' => $publication->title,
        ])
        ->assertJsonFragment([
            'subtitle' => $publication->citation,
        ]);
});
