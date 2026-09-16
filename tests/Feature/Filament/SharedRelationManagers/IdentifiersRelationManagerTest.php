<?php

use App\Enums\PermissionEnums;
use App\Enums\RoleEnums;
use App\Filament\Resources\Datasets\Pages\EditDataset;
use App\Filament\Resources\SharedRelationManagers\IdentifiersRelationManager;
use App\Filament\Resources\Structures\Pages\EditStructure;
use App\Models\Category;
use App\Models\Dataset;
use App\Models\DatasetGroup;
use App\Models\Identifier;
use App\Models\Membrane;
use App\Models\Method;
use App\Models\Permission;
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

function createIdentifiersRmAdmin(array $permissions = []): User
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
function createIdentifiersRmDatasetDependencies(): array
{
    $group = DatasetGroup::factory()->create();
    $method = Method::factory()->create([
        'name' => 'Parallel Artificial Membrane Permeation Assay',
        'abbreviation' => 'PAMPA',
        'parameters' => new MethodParameters([]),
    ]);
    $membrane = Membrane::factory()->create([
        'name' => 'DOPC bilayer',
        'abbreviation' => 'DOPC',
    ]);

    $methodCategory = Category::factory()->create([
        'title' => 'Permeability methods',
        'type' => Category::TYPE_METHOD,
    ]);
    $membraneCategory = Category::factory()->create([
        'title' => 'Phospholipid membranes',
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

function createIdentifiersRmDataset(User $owner): Dataset
{
    $dependencies = createIdentifiersRmDatasetDependencies();

    return Dataset::query()->create([
        'type' => Dataset::TYPE_PASSIVE,
        'name' => 'Reference dataset',
        'comment' => 'Dataset used for identifiers relation manager tests.',
        'dataset_group_id' => $dependencies['group']->id,
        'method_id' => $dependencies['method']->id,
        'membrane_id' => $dependencies['membrane']->id,
        'created_by' => $owner->id,
    ]);
}

function createIdentifiersRmStructure(User $owner, array $attributes = []): Structure
{
    return Structure::factory()->create([
        'user_id' => $owner->id,
        'identifier' => 'MM'.fake()->unique()->numberBetween(1000, 9999),
        'canonical_smiles' => 'CCO',
        ...$attributes,
    ]);
}

test('identifiers relation manager shows structure identifiers on structure owner record', function () {
    $user = createIdentifiersRmAdmin([
        PermissionEnums::STRUCTURE_VIEW->value,
        PermissionEnums::STRUCTURE_EDIT->value,
    ]);
    $structure = createIdentifiersRmStructure($user);
    $relatedIdentifier = Identifier::query()->create([
        'structure_id' => $structure->id,
        'value' => 'MolMeDB name',
        'type' => Identifier::TYPE_NAME,
        'state' => Identifier::STATE_VALIDATED,
        'source_id' => $user->id,
        'source_type' => User::class,
    ]);
    $foreignIdentifier = Identifier::query()->create([
        'structure_id' => createIdentifiersRmStructure($user)->id,
        'value' => 'Foreign identifier',
        'type' => Identifier::TYPE_NAME,
        'state' => Identifier::STATE_VALIDATED,
        'source_id' => $user->id,
        'source_type' => User::class,
    ]);

    $this->actingAs($user);

    Livewire::test(IdentifiersRelationManager::class, [
        'ownerRecord' => $structure,
        'pageClass' => EditStructure::class,
    ])
        ->assertOk()
        ->assertCanSeeTableRecords([$relatedIdentifier])
        ->assertCanNotSeeTableRecords([$foreignIdentifier]);
});

test('identifiers relation manager shows dataset sourced identifiers on dataset owner record', function () {
    $user = createIdentifiersRmAdmin([
        PermissionEnums::DATASET_VIEW->value,
        PermissionEnums::DATASET_EDIT->value,
        PermissionEnums::STRUCTURE_VIEW->value,
    ]);
    $dataset = createIdentifiersRmDataset($user);
    $structure = createIdentifiersRmStructure($user);

    $relatedIdentifier = Identifier::query()->create([
        'structure_id' => $structure->id,
        'value' => 'Imported from dataset',
        'type' => Identifier::TYPE_NAME,
        'state' => Identifier::STATE_VALIDATED,
        'source_id' => $dataset->id,
        'source_type' => Dataset::class,
    ]);
    $foreignIdentifier = Identifier::query()->create([
        'structure_id' => createIdentifiersRmStructure($user)->id,
        'value' => 'Other dataset import',
        'type' => Identifier::TYPE_NAME,
        'state' => Identifier::STATE_VALIDATED,
        'source_id' => createIdentifiersRmDataset($user)->id,
        'source_type' => Dataset::class,
    ]);

    $this->actingAs($user);

    Livewire::test(IdentifiersRelationManager::class, [
        'ownerRecord' => $dataset,
        'pageClass' => EditDataset::class,
    ])
        ->assertOk()
        ->assertCanSeeTableRecords([$relatedIdentifier])
        ->assertCanNotSeeTableRecords([$foreignIdentifier]);
});
