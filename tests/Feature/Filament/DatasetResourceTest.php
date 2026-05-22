<?php

use App\Enums\PermissionEnums;
use App\Enums\RoleEnums;
use App\Filament\Resources\Datasets\DatasetResource;
use App\Filament\Resources\Datasets\Pages\CreateDataset;
use App\Filament\Resources\Datasets\Pages\EditDataset;
use App\Filament\Resources\Datasets\Pages\ListDatasets;
use App\Models\Category;
use App\Models\Dataset;
use App\Models\DatasetGroup;
use App\Models\Membrane;
use App\Models\Method;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
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

function createDatasetAdmin(array $permissions = []): User
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
function createDatasetDependencies(): array
{
    $group = DatasetGroup::factory()->create();
    $method = Method::factory()->create([
        'name' => 'Parallel Artificial Membrane Permeation Assay',
        'abbreviation' => 'PAMPA',
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

function createDataset(User $owner, array $attributes = []): Dataset
{
    $dependencies = createDatasetDependencies();

    return Dataset::query()->create([
        'type' => Dataset::TYPE_PASSIVE,
        'name' => 'Reference passive dataset',
        'comment' => 'Baseline dataset for membrane permeability tests.',
        'dataset_group_id' => $dependencies['group']->id,
        'method_id' => $dependencies['method']->id,
        'membrane_id' => $dependencies['membrane']->id,
        'created_by' => $owner->id,
        ...$attributes,
    ]);
}

test('user with dataset view permission can load the datasets list page', function () {
    $user = createDatasetAdmin([
        PermissionEnums::DATASET_VIEW->value,
    ]);

    $this->actingAs($user);

    Livewire::test(ListDatasets::class)
        ->assertOk();
});

test('user without dataset view permission cannot load the datasets list page', function () {
    $user = createDatasetAdmin();

    $this->actingAs($user);

    Livewire::test(ListDatasets::class)
        ->assertForbidden();
});

test('user with dataset view own permission can load the datasets list page', function () {
    $user = createDatasetAdmin([
        PermissionEnums::DATASET_VIEW_OWN->value,
    ]);

    $this->actingAs($user);

    Livewire::test(ListDatasets::class)
        ->assertOk();
});

test('datasets list page displays existing datasets for authorized user', function () {
    $user = createDatasetAdmin([
        PermissionEnums::DATASET_VIEW->value,
    ]);
    $dataset = createDataset($user, [
        'name' => 'Curated transport dataset',
        'type' => Dataset::TYPE_ACTIVE,
    ]);

    $this->actingAs($user);

    Livewire::test(ListDatasets::class)
        ->assertOk()
        ->assertSee($dataset->name)
        ->assertSee(Dataset::enumType($dataset->type));
});

test('datasets list page shows only own datasets for user with view own permission', function () {
    $user = createDatasetAdmin([
        PermissionEnums::DATASET_VIEW_OWN->value,
    ]);
    $otherUser = createDatasetAdmin();

    $ownDataset = createDataset($user, [
        'name' => 'Own dataset',
    ]);
    $foreignDataset = createDataset($otherUser, [
        'name' => 'Foreign dataset',
    ]);

    $this->actingAs($user);

    Livewire::test(ListDatasets::class)
        ->assertOk()
        ->assertSee($ownDataset->name)
        ->assertDontSee($foreignDataset->name);
});

test('user with dataset edit own permission can load the create dataset page', function () {
    $user = createDatasetAdmin([
        PermissionEnums::DATASET_VIEW_OWN->value,
        PermissionEnums::DATASET_EDIT_OWN->value,
    ]);
    createDatasetDependencies();

    $this->actingAs($user);

    Livewire::test(CreateDataset::class)
        ->assertOk();
});

test('user without dataset edit permission cannot load the create dataset page', function () {
    $user = createDatasetAdmin([
        PermissionEnums::DATASET_VIEW_OWN->value,
    ]);
    createDatasetDependencies();

    $this->actingAs($user);

    Livewire::test(CreateDataset::class)
        ->assertForbidden();
});

test('user with dataset edit own permission can create a dataset and becomes its owner', function () {
    $user = createDatasetAdmin([
        PermissionEnums::DATASET_VIEW_OWN->value,
        PermissionEnums::DATASET_EDIT_OWN->value,
    ]);
    $dependencies = createDatasetDependencies();

    $this->actingAs($user);

    Livewire::test(CreateDataset::class)
        ->fillForm([
            'type' => Dataset::TYPE_PASSIVE,
            'dataset_group_id' => $dependencies['group']->id,
            'method_id' => $dependencies['method']->id,
            'membrane_id' => $dependencies['membrane']->id,
            'name' => 'New passive dataset',
            'comment' => 'Created from Filament test.',
        ])
        ->call('create')
        ->assertNotified()
        ->assertRedirect();

    $dataset = Dataset::query()
        ->where('name', 'New passive dataset')
        ->first();

    expect($dataset)->not->toBeNull()
        ->and($dataset->created_by)->toBe($user->id);
});

test('user with dataset edit own permission can load the edit page for own dataset', function () {
    $user = createDatasetAdmin([
        PermissionEnums::DATASET_VIEW_OWN->value,
        PermissionEnums::DATASET_EDIT_OWN->value,
    ]);
    $dataset = createDataset($user);

    $this->actingAs($user);

    Livewire::test(EditDataset::class, [
        'record' => $dataset->getKey(),
    ])
        ->assertOk()
        ->assertFormSet([
            'type' => $dataset->type,
            'dataset_group_id' => $dataset->dataset_group_id,
            'method_id' => $dataset->method_id,
            'membrane_id' => $dataset->membrane_id,
            'name' => $dataset->name,
            'comment' => $dataset->comment,
        ]);
});

test('user with dataset edit own permission cannot access the edit page for foreign dataset', function () {
    $user = createDatasetAdmin([
        PermissionEnums::DATASET_VIEW_OWN->value,
        PermissionEnums::DATASET_EDIT_OWN->value,
    ]);
    $owner = createDatasetAdmin();
    $dataset = createDataset($owner);

    $this->actingAs($user);

    $this->get(DatasetResource::getUrl('edit', ['record' => $dataset]))
        ->assertNotFound();
});

test('user with dataset edit permission can update any dataset', function () {
    $user = createDatasetAdmin([
        PermissionEnums::DATASET_VIEW->value,
        PermissionEnums::DATASET_EDIT->value,
    ]);
    $owner = createDatasetAdmin();
    $dataset = createDataset($owner, [
        'name' => 'Original dataset',
        'comment' => 'Original comment',
    ]);

    $this->actingAs($user);

    Livewire::test(EditDataset::class, [
        'record' => $dataset->getKey(),
    ])
        ->fillForm([
            'dataset_group_id' => $dataset->dataset_group_id,
            'method_id' => $dataset->method_id,
            'membrane_id' => $dataset->membrane_id,
            'name' => 'Updated dataset',
            'comment' => 'Updated comment',
        ])
        ->call('save')
        ->assertNotified();

    expect($dataset->fresh())
        ->name->toBe('Updated dataset')
        ->comment->toBe('Updated comment');
});

test('dataset policy allows owner scoped edit and delete permissions only on own dataset', function () {
    $owner = createDatasetAdmin([
        PermissionEnums::DATASET_VIEW_OWN->value,
        PermissionEnums::DATASET_EDIT_OWN->value,
        PermissionEnums::DATASET_DELETE_OWN->value,
    ]);
    $otherUser = createDatasetAdmin([
        PermissionEnums::DATASET_VIEW_OWN->value,
        PermissionEnums::DATASET_EDIT_OWN->value,
        PermissionEnums::DATASET_DELETE_OWN->value,
    ]);

    $ownedDataset = createDataset($owner);
    $foreignDataset = createDataset($otherUser);

    expect($owner->can('update', $ownedDataset))->toBeTrue()
        ->and($owner->can('delete', $ownedDataset))->toBeTrue()
        ->and($owner->can('view', $ownedDataset))->toBeTrue()
        ->and($owner->can('update', $foreignDataset))->toBeFalse()
        ->and($owner->can('delete', $foreignDataset))->toBeFalse()
        ->and($owner->can('view', $foreignDataset))->toBeFalse();
});

test('dataset policy allows global edit delete and force delete permissions', function () {
    $user = createDatasetAdmin([
        PermissionEnums::DATASET_EDIT->value,
        PermissionEnums::DATASET_DELETE->value,
        PermissionEnums::DATASET_DELETE_FORCE->value,
    ]);
    $owner = createDatasetAdmin();
    $dataset = createDataset($owner);

    expect($user->can('update', $dataset))->toBeTrue()
        ->and($user->can('delete', $dataset))->toBeTrue()
        ->and($user->can('forceDelete', $dataset))->toBeTrue();
});

test('user with dataset delete own permission cannot delete foreign dataset', function () {
    $user = createDatasetAdmin([
        PermissionEnums::DATASET_DELETE_OWN->value,
    ]);
    $owner = createDatasetAdmin();
    $dataset = createDataset($owner);

    expect($user->can('delete', $dataset))->toBeFalse();
});

test('dataset policy allows global view permission on foreign dataset', function () {
    $user = createDatasetAdmin([
        PermissionEnums::DATASET_VIEW->value,
    ]);
    $owner = createDatasetAdmin();
    $dataset = createDataset($owner);

    expect($user->can('view', $dataset))->toBeTrue();
});

test('dataset resource exposes index create and edit pages', function () {
    expect(DatasetResource::getPages())
        ->toHaveKeys(['index', 'create', 'edit']);
});
