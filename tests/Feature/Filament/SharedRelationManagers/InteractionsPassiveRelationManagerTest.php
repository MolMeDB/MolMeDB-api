<?php

use App\Enums\PermissionEnums;
use App\Enums\RoleEnums;
use App\Filament\Resources\Datasets\Pages\EditDataset;
use App\Filament\Resources\SharedRelationManagers\InteractionsPassiveRelationManager;
use App\Filament\Resources\Structures\Pages\EditStructure;
use App\Models\Category;
use App\Models\Dataset;
use App\Models\DatasetGroup;
use App\Models\InteractionPassive;
use App\Models\Membrane;
use App\Models\Method;
use App\Models\Permission;
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

function createPassiveRmAdmin(array $permissions = []): User
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
function createPassiveRmDatasetDependencies(): array
{
    $group = DatasetGroup::factory()->create();
    $method = Method::factory()->create([
        'name' => 'Passive transport assay',
        'abbreviation' => 'PTA',
        'parameters' => new MethodParameters([]),
    ]);
    $membrane = Membrane::factory()->create([
        'name' => 'Passive membrane',
        'abbreviation' => 'PM',
    ]);

    $methodCategory = Category::factory()->create([
        'title' => 'Passive methods',
        'type' => Category::TYPE_METHOD,
    ]);
    $membraneCategory = Category::factory()->create([
        'title' => 'Passive membranes',
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

function createPassiveRmDataset(User $owner, array $attributes = []): Dataset
{
    $dependencies = createPassiveRmDatasetDependencies();

    return Dataset::query()->create([
        'type' => Dataset::TYPE_PASSIVE,
        'name' => 'Passive dataset',
        'comment' => 'Dataset used for passive interactions relation manager tests.',
        'dataset_group_id' => $dependencies['group']->id,
        'method_id' => $dependencies['method']->id,
        'membrane_id' => $dependencies['membrane']->id,
        'created_by' => $owner->id,
        ...$attributes,
    ]);
}

function createPassiveRmStructure(User $owner, array $attributes = []): Structure
{
    return Structure::factory()->create([
        'user_id' => $owner->id,
        'identifier' => 'MM'.fake()->unique()->numberBetween(1000, 9999),
        'canonical_smiles' => 'CCO',
        ...$attributes,
    ]);
}

function createPassiveRmPublication(array $attributes = []): Publication
{
    return Publication::factory()->create([
        'citation' => 'Reference publication',
        ...$attributes,
    ]);
}

function createPassiveRmInteraction(User $owner, array $attributes = []): InteractionPassive
{
    $dataset = $attributes['dataset'] ?? createPassiveRmDataset($owner);
    $structure = $attributes['structure'] ?? createPassiveRmStructure($owner);
    $publication = $attributes['publication'] ?? createPassiveRmPublication();

    unset($attributes['dataset'], $attributes['structure'], $attributes['publication']);

    return InteractionPassive::query()->create([
        'dataset_id' => $dataset->id,
        'structure_id' => $structure->id,
        'publication_id' => $publication->id,
        'note' => 'Passive relation manager test record.',
        ...$attributes,
    ]);
}

test('passive interactions relation manager shows interactions for structure only', function () {
    $user = createPassiveRmAdmin([
        PermissionEnums::DATASET_VIEW->value,
    ]);

    $structure = createPassiveRmStructure($user);
    $relatedInteraction = createPassiveRmInteraction($user, [
        'structure' => $structure,
        'note' => 'Related passive interaction',
    ]);
    $foreignInteraction = createPassiveRmInteraction($user, [
        'note' => 'Foreign passive interaction',
    ]);

    $this->actingAs($user);

    Livewire::test(InteractionsPassiveRelationManager::class, [
        'ownerRecord' => $structure,
        'pageClass' => EditStructure::class,
    ])
        ->assertOk()
        ->assertCanSeeTableRecords([$relatedInteraction])
        ->assertCanNotSeeTableRecords([$foreignInteraction]);
});

test('passive interactions relation manager is visible only for passive datasets', function () {
    $owner = createPassiveRmAdmin([
        PermissionEnums::DATASET_VIEW->value,
    ]);
    $passiveDataset = createPassiveRmDataset($owner, ['type' => Dataset::TYPE_PASSIVE]);
    $internalCosmoDataset = createPassiveRmDataset($owner, ['type' => Dataset::TYPE_PASSIVE_INTERNAL_COSMO]);
    $activeDataset = createPassiveRmDataset($owner, ['type' => Dataset::TYPE_ACTIVE]);

    $this->actingAs($owner);

    expect(InteractionsPassiveRelationManager::canViewForRecord($passiveDataset, EditDataset::class))->toBeTrue()
        ->and(InteractionsPassiveRelationManager::canViewForRecord($internalCosmoDataset, EditDataset::class))->toBeTrue()
        ->and(InteractionsPassiveRelationManager::canViewForRecord($activeDataset, EditDataset::class))->toBeFalse();
});
