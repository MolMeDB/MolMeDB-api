<?php

use App\Enums\PermissionEnums;
use App\Enums\RoleEnums;
use App\Filament\Resources\Methods\Pages\EditMethod;
use App\Filament\Resources\SharedRelationManagers\KeywordsRelationManager;
use App\Models\Category;
use App\Models\Dataset;
use App\Models\DatasetGroup;
use App\Models\Keyword;
use App\Models\Membrane;
use App\Models\Method;
use App\Models\Permission;
use App\Models\Role;
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

function createKeywordsRmAdmin(array $permissions = []): User
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
function createKeywordsRmDatasetDependencies(): array
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

function createKeywordsRmDataset(User $owner): Dataset
{
    $dependencies = createKeywordsRmDatasetDependencies();

    return Dataset::query()->create([
        'type' => Dataset::TYPE_PASSIVE,
        'name' => 'Reference dataset',
        'comment' => 'Dataset used for keywords relation manager tests.',
        'dataset_group_id' => $dependencies['group']->id,
        'method_id' => $dependencies['method']->id,
        'membrane_id' => $dependencies['membrane']->id,
        'created_by' => $owner->id,
    ]);
}

test('keywords relation manager shows keywords for method owner record', function () {
    $user = createKeywordsRmAdmin([
        PermissionEnums::MEMBRANE_METHOD_VIEW->value,
        PermissionEnums::MEMBRANE_METHOD_EDIT->value,
    ]);
    $dataset = createKeywordsRmDataset($user);
    $method = $dataset->method;

    $relatedKeyword = Keyword::query()->create([
        'value' => 'permeability',
        'model_id' => $method->id,
        'model_type' => Method::class,
    ]);
    Keyword::query()->create([
        'value' => 'foreign-keyword',
        'model_id' => createKeywordsRmDataset($user)->method->id,
        'model_type' => Method::class,
    ]);

    $this->actingAs($user);

    Livewire::test(KeywordsRelationManager::class, [
        'ownerRecord' => $method,
        'pageClass' => EditMethod::class,
    ])
        ->assertOk()
        ->assertSee($relatedKeyword->value)
        ->assertDontSee('foreign-keyword');
});
