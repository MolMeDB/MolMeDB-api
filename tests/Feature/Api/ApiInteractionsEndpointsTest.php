<?php

require_once __DIR__.'/api_test_helpers.php';

beforeEach(function () {
    prepareApiEndpointTestEnvironment();
});

afterEach(function () {
    resetApiRouteCdkDepictState();
    resetApiRouteRdkitState();
});

test('passive interactions by structure endpoint returns matching interactions', function () {
    $structure = createApiStructure(['identifier' => 'MM1001']);
    createApiPassiveInteraction([
        'structure' => $structure,
        'note' => 'Passive interaction for structure',
    ]);

    $this->getJson(apiRoutePath('api/interactions/passive/structure/MM1001'))
        ->assertOk()
        ->assertJsonFragment([
            'note' => 'Passive interaction for structure',
        ]);
});

test('active interactions by structure endpoint returns matching interactions', function () {
    $structure = createApiStructure(['identifier' => 'MM1002']);
    createApiActiveInteraction([
        'structure' => $structure,
        'note' => 'Active interaction for structure',
    ]);

    $this->getJson(apiRoutePath('api/interactions/active/structure/MM1002'))
        ->assertOk()
        ->assertJsonFragment([
            'note' => 'Active interaction for structure',
        ]);
});
