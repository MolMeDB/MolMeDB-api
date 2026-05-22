<?php

use App\Enums\PermissionEnums;
use App\Enums\RoleEnums;
use App\Filament\Resources\InteractionPassives\InteractionPassiveResource;
use App\Filament\Resources\InteractionPassives\Pages\CreateInteractionPassive;
use App\Filament\Resources\InteractionPassives\Pages\EditInteractionPassive;
use App\Filament\Resources\InteractionPassives\Pages\ListInteractionPassives;
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

function createInteractionPassiveAdmin(array $permissions = []): User
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
function createPassiveDatasetDependencies(): array
{
    $group = DatasetGroup::factory()->create();
    $method = Method::factory()->create([
        'name' => 'Passive transport assay',
        'abbreviation' => 'PTA',
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

function createPassiveDataset(User $owner, array $attributes = []): Dataset
{
    $dependencies = createPassiveDatasetDependencies();

    return Dataset::query()->create([
        'type' => Dataset::TYPE_PASSIVE,
        'name' => 'Passive dataset',
        'comment' => 'Dataset used for passive interactions.',
        'dataset_group_id' => $dependencies['group']->id,
        'method_id' => $dependencies['method']->id,
        'membrane_id' => $dependencies['membrane']->id,
        'created_by' => $owner->id,
        ...$attributes,
    ]);
}

function createInteractionPassive(User $datasetOwner, array $attributes = []): InteractionPassive
{
    $dataset = $attributes['dataset'] ?? createPassiveDataset($datasetOwner);
    $structure = Structure::factory()->create([
        'identifier' => 'MM-IP-'.fake()->unique()->numberBetween(1000, 9999),
    ]);
    $publication = Publication::factory()->create([
        'citation' => 'Passive interaction publication '.fake()->unique()->numberBetween(100, 999),
    ]);

    unset($attributes['dataset']);

    return InteractionPassive::query()->create([
        'dataset_id' => $dataset->id,
        'structure_id' => $structure->id,
        'publication_id' => $publication->id,
        'note' => 'Passive transport record.',
        'temperature' => 25,
        'ph' => 6.8,
        'charge' => '0',
        'x_min' => 1.2,
        'x_min_accuracy' => 0.1,
        'gpen' => 2.3,
        'gpen_accuracy' => 0.2,
        'gwat' => 3.4,
        'gwat_accuracy' => 0.3,
        'logk' => 4.5,
        'logk_accuracy' => 0.4,
        'logperm' => 5.6,
        'logperm_accuracy' => 0.5,
        ...$attributes,
    ]);
}

test('user with dataset view permission can load the passive interactions list page', function () {
    $user = createInteractionPassiveAdmin([
        PermissionEnums::DATASET_VIEW->value,
    ]);

    $this->actingAs($user);

    Livewire::test(ListInteractionPassives::class)
        ->assertOk();
});

test('user without dataset view permission cannot load the passive interactions list page', function () {
    $user = createInteractionPassiveAdmin();

    $this->actingAs($user);

    Livewire::test(ListInteractionPassives::class)
        ->assertForbidden();
});

test('user with dataset view own permission sees only passive interactions from own datasets', function () {
    $user = createInteractionPassiveAdmin([
        PermissionEnums::DATASET_VIEW_OWN->value,
    ]);
    $otherUser = createInteractionPassiveAdmin();

    $ownInteraction = createInteractionPassive($user, [
        'note' => 'Own passive interaction',
    ]);
    $foreignInteraction = createInteractionPassive($otherUser, [
        'note' => 'Foreign passive interaction',
    ]);

    $this->actingAs($user);

    Livewire::test(ListInteractionPassives::class)
        ->assertOk()
        ->assertSee($ownInteraction->note)
        ->assertDontSee($foreignInteraction->note);
});

test('passive interactions list page displays existing interaction data for authorized user', function () {
    $user = createInteractionPassiveAdmin([
        PermissionEnums::DATASET_VIEW->value,
    ]);
    $interaction = createInteractionPassive($user, [
        'note' => 'Diffusion through membrane',
    ]);

    $this->actingAs($user);

    Livewire::test(ListInteractionPassives::class)
        ->assertOk()
        ->assertSee($interaction->structure->identifier)
        ->assertSee($interaction->dataset->name)
        ->assertSee($interaction->note);
});

// test('user with dataset edit own permission can load the create passive interaction page', function () {
//     $user = createInteractionPassiveAdmin([
//         PermissionEnums::DATASET_VIEW_OWN->value,
//         PermissionEnums::DATASET_EDIT_OWN->value,
//     ]);

//     $this->actingAs($user);

//     Livewire::test(CreateInteractionPassive::class)
//         ->assertOk();
// });

// test('user without dataset edit permission cannot load the create passive interaction page', function () {
//     $user = createInteractionPassiveAdmin([
//         PermissionEnums::DATASET_VIEW_OWN->value,
//     ]);

//     $this->actingAs($user);

//     Livewire::test(CreateInteractionPassive::class)
//         ->assertForbidden();
// });

test('user with dataset edit own permission can load the edit page for interaction in own dataset', function () {
    $user = createInteractionPassiveAdmin([
        PermissionEnums::DATASET_VIEW_OWN->value,
        PermissionEnums::DATASET_EDIT_OWN->value,
    ]);
    $interaction = createInteractionPassive($user);

    $this->actingAs($user);

    Livewire::test(EditInteractionPassive::class, [
        'record' => $interaction->getKey(),
    ])
        ->assertOk()
        ->assertFormSet([
            'dataset_id' => $interaction->dataset_id,
            'structure_id' => $interaction->structure_id,
            'publication_id' => $interaction->publication_id,
            'note' => $interaction->note,
        ]);
});

test('user with dataset edit own permission gets not found for interaction in foreign dataset when only own datasets are visible', function () {
    $user = createInteractionPassiveAdmin([
        PermissionEnums::DATASET_VIEW_OWN->value,
        PermissionEnums::DATASET_EDIT_OWN->value,
    ]);
    $owner = createInteractionPassiveAdmin();
    $interaction = createInteractionPassive($owner);

    $this->actingAs($user);

    $this->get(InteractionPassiveResource::getUrl('edit', ['record' => $interaction]))
        ->assertNotFound();
});

test('user with dataset view permission and dataset edit own permission cannot load the edit page for foreign passive interaction', function () {
    $user = createInteractionPassiveAdmin([
        PermissionEnums::DATASET_VIEW->value,
        PermissionEnums::DATASET_EDIT_OWN->value,
    ]);
    $owner = createInteractionPassiveAdmin();
    $interaction = createInteractionPassive($owner);

    $this->actingAs($user);

    $this->get(InteractionPassiveResource::getUrl('edit', ['record' => $interaction]))
        ->assertForbidden();
});

test('interaction passive policy delegates dataset permissions for view update and delete', function () {
    $user = createInteractionPassiveAdmin([
        PermissionEnums::DATASET_VIEW_OWN->value,
        PermissionEnums::DATASET_EDIT_OWN->value,
        PermissionEnums::DATASET_DELETE_OWN->value,
    ]);
    $interaction = createInteractionPassive($user);
    $foreignOwner = createInteractionPassiveAdmin();
    $foreignInteraction = createInteractionPassive($foreignOwner);

    expect($user->can('view', $interaction))->toBeTrue()
        ->and($user->can('update', $interaction))->toBeTrue()
        ->and($user->can('delete', $interaction))->toBeTrue()
        ->and($user->can('view', $foreignInteraction))->toBeFalse()
        ->and($user->can('update', $foreignInteraction))->toBeFalse()
        ->and($user->can('delete', $foreignInteraction))->toBeFalse();
});

test('interaction passive resource exposes index and edit pages', function () {
    expect(InteractionPassiveResource::getPages())
        ->toHaveKeys(['index', 'edit']);
});
