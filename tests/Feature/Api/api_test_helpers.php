<?php

use App\Models\Category;
use App\Models\Dataset;
use App\Models\DatasetGroup;
use App\Models\InteractionActive;
use App\Models\InteractionPassive;
use App\Models\Membrane;
use App\Models\Method;
use App\Models\Protein;
use App\Models\Publication;
use App\Models\Structure;
use App\Models\User;
use App\ValueObjects\MethodParameters;
use Illuminate\Support\Facades\Http;
use Modules\CdkDepict\CdkDepict;
use Modules\Rdkit\Rdkit;

function prepareApiEndpointTestEnvironment(): void
{
    config()->set('services.cdk_depict_url', 'https://cdk-depict.test');
    config()->set('services.rdkit.url', 'https://rdkit.test');

    resetApiRouteCdkDepictState();
    resetApiRouteRdkitState();

    Http::fake([
        'https://cdk-depict.test/test' => Http::response([], 200),
        'https://rdkit.test/test' => Http::response([], 200),
        'https://rdkit.test/structure/canonize*' => Http::response([
            'data' => 'OCC',
        ], 200),
    ]);
}

function resetApiRouteCdkDepictState(): void
{
    Closure::bind(function (): void {
        self::$STATUS = false;
        self::$url_parameters = ['host' => ''];
    }, null, CdkDepict::class)();
}

function resetApiRouteRdkitState(): void
{
    Closure::bind(function (): void {
        self::$STATUS = false;
        self::$url_parameters = ['host' => ''];
    }, null, Rdkit::class)();
}

function apiRoutePath(string $suffix)
{
    $normalizedSuffix = trim($suffix, '/');
    $candidates = array_values(array_unique([
        $normalizedSuffix,
        'api/'.$normalizedSuffix,
    ]));

    foreach ($candidates as $candidate) {
        $route = collect(app('router')->getRoutes()->getRoutes())->first(
            fn ($route): bool => routeUriMatchesRequestedPath($route->uri(), $candidate)
        );

        if ($route) {
            return '/'.$candidate;
        }
    }

    expect(null)->not->toBeNull("Route ending with [{$normalizedSuffix}] was not found.");
}

function routeUriMatchesRequestedPath(string $routeUri, string $requestedPath): bool
{
    $routeSegments = explode('/', trim($routeUri, '/'));
    $requestedSegments = explode('/', trim($requestedPath, '/'));

    if (count($routeSegments) !== count($requestedSegments)) {
        return false;
    }

    foreach ($routeSegments as $index => $routeSegment) {
        if (preg_match('/^\{[^}]+\}$/', $routeSegment) === 1) {
            continue;
        }

        if ($routeSegment !== $requestedSegments[$index]) {
            return false;
        }
    }

    return true;
}

function createApiRootCategory(int $type, string $title): Category
{
    return Category::factory()->create([
        'parent_id' => -1,
        'title' => $title,
        'type' => $type,
        'order' => 1,
    ]);
}

function createApiChildCategory(Category $parent, string $title): Category
{
    return Category::factory()->create([
        'parent_id' => $parent->id,
        'title' => $title,
        'type' => $parent->type,
        'order' => 1,
    ]);
}

function createApiMembrane(array $attributes = [], ?Category $category = null): Membrane
{
    $membrane = Membrane::factory()->create([
        'name' => 'DOPC bilayer',
        'abbreviation' => 'DOPC',
        'description' => 'Reference membrane.',
        ...$attributes,
    ]);

    if ($category) {
        $membrane->categories()->syncWithPivotValues(
            [$category->id],
            ['model_type' => Membrane::class],
        );
    }

    return $membrane->refresh();
}

function createApiMethod(array $attributes = [], ?Category $category = null): Method
{
    $method = Method::factory()->create([
        'name' => 'Parallel Artificial Membrane Permeation Assay',
        'abbreviation' => 'PAMPA',
        'description' => 'Reference assay.',
        'parameters' => new MethodParameters([]),
        ...$attributes,
    ]);

    if ($category) {
        $method->categories()->syncWithPivotValues(
            [$category->id],
            ['model_type' => Method::class],
        );
    }

    return $method->refresh();
}

function createApiProtein(array $attributes = [], ?Category $category = null): Protein
{
    $protein = Protein::factory()->create([
        'uniprot_id' => 'P12345',
        ...$attributes,
    ]);

    if ($category) {
        $protein->categories()->syncWithPivotValues(
            [$category->id],
            ['model_type' => Protein::class],
        );
    }

    return $protein->refresh();
}

function createApiPublication(array $attributes = []): Publication
{
    return Publication::factory()->create([
        'citation' => 'Doe J.: Example publication.',
        'title' => 'Example publication',
        'doi' => null,
        'identifier' => null,
        'identifier_source' => null,
        'year' => 2024,
        ...$attributes,
    ]);
}

function createApiStructure(array $attributes = [])
{
    return Structure::factory()->create([
        'identifier' => 'MM'.fake()->unique()->numberBetween(1000, 9999),
        'canonical_smiles' => 'CCO',
        'inchi' => 'InChI=1S/C2H6O',
        'inchikey' => 'LFQSCWFLJHTTHZ-UHFFFAOYSA-N',
        ...$attributes,
    ]);
}

function createApiDataset(array $attributes = []): Dataset
{
    $membraneCategory = createApiRootCategory(Category::TYPE_MEMBRANE, 'Membranes');
    $methodCategory = createApiRootCategory(Category::TYPE_METHOD, 'Methods');

    $membrane = $attributes['membrane'] ?? createApiMembrane(category: $membraneCategory);
    $method = $attributes['method'] ?? createApiMethod(category: $methodCategory);
    $group = $attributes['group'] ?? DatasetGroup::factory()->create([
        'name' => 'Example dataset group',
    ]);
    $publication = $attributes['publication'] ?? createApiPublication();
    $owner = $attributes['owner'] ?? User::factory()->create();

    unset($attributes['membrane'], $attributes['method'], $attributes['group'], $attributes['publication'], $attributes['owner']);

    $dataset = Dataset::query()->create([
        'type' => Dataset::TYPE_PASSIVE,
        'name' => 'Example dataset',
        'comment' => 'Example dataset comment',
        'membrane_id' => $membrane->id,
        'method_id' => $method->id,
        'dataset_group_id' => $group->id,
        'created_by' => $owner->id,
        ...$attributes,
    ]);

    $dataset->publications()->attach($publication->id, [
        'model_type' => Dataset::class,
    ]);

    return $dataset->refresh();
}

function createApiPassiveInteraction(array $attributes = []): InteractionPassive
{
    $structure = $attributes['structure'] ?? createApiStructure();
    $dataset = $attributes['dataset'] ?? createApiDataset([
        'type' => Dataset::TYPE_PASSIVE,
    ]);
    $publication = $attributes['publication'] ?? createApiPublication();

    unset($attributes['structure'], $attributes['dataset'], $attributes['publication']);

    return InteractionPassive::query()->create([
        'dataset_id' => $dataset->id,
        'structure_id' => $structure->id,
        'publication_id' => $publication->id,
        'temperature' => 25.0,
        'ph' => 7.4,
        'charge' => '0',
        'note' => 'Passive interaction',
        'x_min' => 1.1,
        'x_min_accuracy' => 0.1,
        'gpen' => 1.2,
        'gpen_accuracy' => 0.1,
        'gwat' => 1.3,
        'gwat_accuracy' => 0.1,
        'logk' => 1.4,
        'logk_accuracy' => 0.1,
        'logperm' => 1.5,
        'logperm_accuracy' => 0.1,
        ...$attributes,
    ]);
}

function createApiActiveInteraction(array $attributes = []): InteractionActive
{
    $structure = $attributes['structure'] ?? createApiStructure();
    $dataset = $attributes['dataset'] ?? createApiDataset([
        'type' => Dataset::TYPE_ACTIVE,
    ]);
    $publication = $attributes['publication'] ?? createApiPublication();
    $proteinCategory = $attributes['protein_category'] ?? createApiRootCategory(Category::TYPE_PROTEIN, 'Proteins');
    $protein = $attributes['protein'] ?? createApiProtein(category: $proteinCategory);
    $category = $attributes['category'] ?? Category::factory()->create([
        'title' => 'Carrier-mediated',
        'type' => Category::TYPE_ACTIVE_INTERACTION,
        'parent_id' => -1,
    ]);

    unset($attributes['structure'], $attributes['dataset'], $attributes['publication'], $attributes['protein_category'], $attributes['protein'], $attributes['category']);

    return InteractionActive::query()->create([
        'dataset_id' => $dataset->id,
        'structure_id' => $structure->id,
        'protein_id' => $protein->id,
        'publication_id' => $publication->id,
        'category_id' => $category->id,
        'temperature' => 37.0,
        'ph' => 7.4,
        'charge' => '+1',
        'note' => 'Active interaction',
        'km' => 1.1,
        'km_accuracy' => 0.1,
        'ec50' => 1.2,
        'ec50_accuracy' => 0.1,
        'ki' => 1.3,
        'ki_accuracy' => 0.1,
        'ic50' => 1.4,
        'ic50_accuracy' => 0.1,
        ...$attributes,
    ]);
}
