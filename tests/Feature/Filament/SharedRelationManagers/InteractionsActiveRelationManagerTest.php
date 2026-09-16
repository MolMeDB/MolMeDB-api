<?php

use App\Enums\PermissionEnums;
use App\Enums\RoleEnums;
use App\Filament\Resources\Datasets\Pages\EditDataset;
use App\Filament\Resources\SharedRelationManagers\InteractionsActiveRelationManager;
use App\Filament\Resources\Structures\Pages\EditStructure;
use App\Models\Category;
use App\Models\Dataset;
use App\Models\DatasetGroup;
use App\Models\InteractionActive;
use App\Models\Membrane;
use App\Models\Method;
use App\Models\Permission;
use App\Models\Protein;
use App\Models\Publication;
use App\Models\Role;
use App\Models\Structure;
use App\Models\User;
use App\ValueObjects\MethodParameters;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    collect(PermissionEnums::cases())->each(function (PermissionEnums $permissionEnum): void {
        Permission::query()->firstOrCreate([
            'name' => $permissionEnum->value,
            'guard_name' => 'web',
        ], [
            'description' => $permissionEnum->description(),
        ]);
    });

    collect(RoleEnums::cases())->each(function (RoleEnums $roleEnum): void {
        Role::query()->firstOrCreate([
            'name' => $roleEnum->value,
            'guard_name' => 'web',
        ]);
    });

    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

function createActiveRmAdmin(array $permissions = []): User
{
    $adminRole = Role::query()->firstOrCreate([
        'name' => RoleEnums::ADMIN->value,
        'guard_name' => 'web',
    ]);

    $user = User::factory()->create();
    $user->syncRoles([$adminRole]);

    if ($permissions !== []) {
        $adminRole->givePermissionTo(
            Permission::query()->whereIn('name', $permissions)->get(),
        );
    }

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    return $user->refresh();
}

/**
 * @return array{group: DatasetGroup, method: Method, membrane: Membrane}
 */
function createActiveRmDatasetDependencies(): array
{
    $group = DatasetGroup::factory()->create();
    $method = Method::factory()->create([
        'name' => 'Active transport assay',
        'abbreviation' => 'ATA',
        'parameters' => new MethodParameters([]),
    ]);
    $membrane = Membrane::factory()->create([
        'name' => 'Transport membrane',
        'abbreviation' => 'TM',
    ]);

    $methodCategory = Category::factory()->create([
        'title' => 'Transport methods',
        'type' => Category::TYPE_METHOD,
    ]);
    $membraneCategory = Category::factory()->create([
        'title' => 'Transport membranes',
        'type' => Category::TYPE_MEMBRANE,
    ]);

    DB::table('model_has_categories')->insert([
        [
            'category_id' => $methodCategory->id,
            'model_id' => $method->id,
            'model_type' => Method::class,
        ],
        [
            'category_id' => $membraneCategory->id,
            'model_id' => $membrane->id,
            'model_type' => Membrane::class,
        ],
    ]);

    return [
        'group' => $group,
        'method' => $method,
        'membrane' => $membrane,
    ];
}

function createActiveRmDataset(User $owner, array $attributes = []): Dataset
{
    $dependencies = createActiveRmDatasetDependencies();

    return Dataset::query()->create([
        'type' => Dataset::TYPE_ACTIVE,
        'name' => 'Active dataset',
        'comment' => 'Dataset used for active interactions relation manager tests.',
        'dataset_group_id' => $dependencies['group']->id,
        'method_id' => $dependencies['method']->id,
        'membrane_id' => $dependencies['membrane']->id,
        'created_by' => $owner->id,
        ...$attributes,
    ]);
}

function createActiveRmStructure(User $owner, array $attributes = [])
{
    return Structure::factory()->create([
        'user_id' => $owner->id,
        'identifier' => 'MM'.fake()->unique()->numberBetween(1000, 9999),
        'canonical_smiles' => 'CCO',
        ...$attributes,
    ]);
}

function createActiveRmInteractionCategory(array $attributes = []): Category
{
    return Category::factory()->create([
        'title' => 'Carrier-mediated',
        'type' => Category::TYPE_ACTIVE_INTERACTION,
        ...$attributes,
    ]);
}

function createActiveRmPublication(array $attributes = []): Publication
{
    return Publication::factory()->create([
        'citation' => 'Reference publication',
        ...$attributes,
    ]);
}

function createActiveRmInteraction(User $owner, array $attributes = []): InteractionActive
{
    $dataset = $attributes['dataset'] ?? createActiveRmDataset($owner);
    $structure = $attributes['structure'] ?? createActiveRmStructure($owner);
    $protein = $attributes['protein'] ?? Protein::factory()->create([
        'uniprot_id' => 'P'.fake()->unique()->numberBetween(10000, 99999),
    ]);
    $publication = $attributes['publication'] ?? createActiveRmPublication();
    $category = $attributes['category'] ?? createActiveRmInteractionCategory();

    unset($attributes['dataset'], $attributes['structure'], $attributes['protein'], $attributes['publication'], $attributes['category']);

    return InteractionActive::query()->create([
        'dataset_id' => $dataset->id,
        'structure_id' => $structure->id,
        'protein_id' => $protein->id,
        'publication_id' => $publication->id,
        'category_id' => $category->id,
        'note' => 'Active relation manager test record.',
        ...$attributes,
    ]);
}

test('active interactions relation manager shows interactions for structure only', function () {
    $user = createActiveRmAdmin([
        PermissionEnums::DATASET_VIEW->value,
    ]);

    $structure = createActiveRmStructure($user);
    $relatedInteraction = createActiveRmInteraction($user, [
        'structure' => $structure,
        'note' => 'Related active interaction',
    ]);
    $foreignInteraction = createActiveRmInteraction($user, [
        'note' => 'Foreign active interaction',
    ]);

    $this->actingAs($user);

    Livewire::test(InteractionsActiveRelationManager::class, [
        'ownerRecord' => $structure,
        'pageClass' => EditStructure::class,
    ])
        ->assertOk()
        ->assertCanSeeTableRecords([$relatedInteraction])
        ->assertCanNotSeeTableRecords([$foreignInteraction]);
});

test('active interactions relation manager is visible only for active datasets', function () {
    $owner = createActiveRmAdmin([
        PermissionEnums::DATASET_VIEW->value,
    ]);
    $activeDataset = createActiveRmDataset($owner, ['type' => Dataset::TYPE_ACTIVE]);
    $passiveDataset = createActiveRmDataset($owner, ['type' => Dataset::TYPE_PASSIVE]);

    $this->actingAs($owner);

    expect(InteractionsActiveRelationManager::canViewForRecord($activeDataset, EditDataset::class))->toBeTrue()
        ->and(InteractionsActiveRelationManager::canViewForRecord($passiveDataset, EditDataset::class))->toBeFalse();
});
