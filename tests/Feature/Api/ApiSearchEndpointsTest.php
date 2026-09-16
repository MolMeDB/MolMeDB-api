<?php

use App\Models\Identifier;

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

test('search structures endpoint marks records without identifier as unavailable', function () {
    $structure = createApiStructure([
        'identifier' => null,
    ]);

    Identifier::factory()->create([
        'structure_id' => $structure->id,
        'type' => Identifier::TYPE_NAME,
        'value' => 'Pending molecule',
        'state' => Identifier::STATE_VALIDATED,
    ]);

    $this->getJson(apiRoutePath('api/search/structures').'?query=Pending')
        ->assertOk()
        ->assertJsonPath('data.0.title', 'Pending molecule')
        ->assertJsonPath('data.0.subtitle', null)
        ->assertJsonPath('data.0.link', null)
        ->assertJsonPath('data.0.isAvailable', false)
        ->assertJsonPath('data.0.availabilityMessage', 'This molecule record is being prepared.');
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
