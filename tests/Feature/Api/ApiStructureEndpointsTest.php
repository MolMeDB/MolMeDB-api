<?php

require_once __DIR__.'/api_test_helpers.php';

use App\Models\Category;
use App\Models\Dataset;
use App\Models\User;

beforeEach(function () {
    prepareApiEndpointTestEnvironment();
});

afterEach(function () {
    resetApiRouteCdkDepictState();
    resetApiRouteRdkitState();
});

test('structure show endpoint returns structure resource', function () {
    $structure = createApiStructure([
        'identifier' => 'MM3001',
    ]);

    $this->getJson(apiRoutePath('api/structure/MM3001'))
        ->assertOk()
        ->assertJsonPath('data.id', $structure->id)
        ->assertJsonPath('data.identifier', 'MM3001');
});

test('structure mol 3d endpoint returns stored mol content', function () {
    $structure = createApiStructure([
        'identifier' => 'MM3002',
        'molfile_3d' => 'MOLFILE CONTENT',
    ]);

    $response = $this->get(apiRoutePath("api/structure/mol/3d/{$structure->identifier}"));

    $response->assertOk();
    expect($response->getContent())->toBe('MOLFILE CONTENT')
        ->and($response->headers->get('content-type'))->toContain('chemical/x-mdl-sdfile');
});

test('structure canonize smiles endpoint requires authentication', function () {
    $this->getJson(apiRoutePath('api/structure/mol/canonize_smiles/CCO'))
        ->assertUnauthorized();
});

test('structure canonize smiles endpoint returns canonized smiles for authenticated user', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $this->getJson(apiRoutePath('api/structure/mol/canonize_smiles/CCO'))
        ->assertOk()
        ->assertJson([
            'request_smiles' => 'CCO',
            'canonized_smiles' => 'OCC',
        ]);
});

test('structure form select membranes endpoint returns grouped membrane tree', function () {
    $membraneRoot = createApiRootCategory(Category::TYPE_MEMBRANE, 'Phospholipids');
    $membraneChild = createApiChildCategory($membraneRoot, 'Bilayers');
    $methodRoot = createApiRootCategory(Category::TYPE_METHOD, 'Transport methods');
    $methodChild = createApiChildCategory($methodRoot, 'PAMPA methods');

    $membrane = createApiMembrane(category: $membraneChild);
    $method = createApiMethod(category: $methodChild);
    $structure = createApiStructure(['identifier' => 'MM4001']);
    $dataset = createApiDataset([
        'membrane' => $membrane,
        'method' => $method,
        'type' => Dataset::TYPE_PASSIVE,
    ]);

    createApiPassiveInteraction([
        'structure' => $structure,
        'dataset' => $dataset,
    ]);

    $this->getJson(apiRoutePath('api/structure/MM4001/form/select/membranes'))
        ->assertOk()
        ->assertJsonFragment([
            'placeholder' => 'Phospholipids',
        ])
        ->assertJsonFragment([
            'label' => $membrane->abbreviation,
        ]);
});

test('structure form select methods endpoint returns grouped method tree', function () {
    $membraneRoot = createApiRootCategory(Category::TYPE_MEMBRANE, 'Phospholipids');
    $membraneChild = createApiChildCategory($membraneRoot, 'Bilayers');
    $methodRoot = createApiRootCategory(Category::TYPE_METHOD, 'Transport methods');
    $methodChild = createApiChildCategory($methodRoot, 'PAMPA methods');

    $membrane = createApiMembrane(category: $membraneChild);
    $method = createApiMethod(category: $methodChild);
    $structure = createApiStructure(['identifier' => 'MM4002']);
    $dataset = createApiDataset([
        'membrane' => $membrane,
        'method' => $method,
        'type' => Dataset::TYPE_PASSIVE,
    ]);

    createApiPassiveInteraction([
        'structure' => $structure,
        'dataset' => $dataset,
    ]);

    $this->getJson(apiRoutePath('api/structure/MM4002/form/select/methods'))
        ->assertOk()
        ->assertJsonFragment([
            'placeholder' => 'Transport methods',
        ])
        ->assertJsonFragment([
            'label' => $method->abbreviation,
        ]);
});

test('structure similarities endpoint returns related structures payload', function () {
    $parent = createApiStructure([
        'identifier' => 'MM5001',
    ]);
    createApiStructure([
        'identifier' => 'MM5002',
        'parent_id' => $parent->id,
    ]);

    $this->getJson(apiRoutePath('api/structure/MM5001/similarities'))
        ->assertOk()
        ->assertJsonStructure([
            'related_structures',
            'similar_structures',
        ]);
});
