<?php

use App\Enums\PermissionEnums;
use App\Enums\RoleEnums;
use App\Filament\Resources\InteractionActives\InteractionActiveResource;
use App\Filament\Resources\InteractionActives\Pages\CreateInteractionActive;
use App\Filament\Resources\InteractionActives\Pages\EditInteractionActive;
use App\Filament\Resources\InteractionActives\Pages\ListInteractionActives;
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

function createInteractionActiveAdmin(array $permissions = []): User
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
function createInteractionDatasetDependencies(): array
{
    $group = DatasetGroup::factory()->create();
    $method = Method::factory()->create([
        'name' => 'Active transport assay',
        'abbreviation' => 'ATA',
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

function createInteractionDataset(User $owner, array $attributes = []): Dataset
{
    $dependencies = createInteractionDatasetDependencies();

    return Dataset::query()->create([
        'type' => Dataset::TYPE_ACTIVE,
        'name' => 'Transport dataset',
        'comment' => 'Dataset used for active interactions.',
        'dataset_group_id' => $dependencies['group']->id,
        'method_id' => $dependencies['method']->id,
        'membrane_id' => $dependencies['membrane']->id,
        'created_by' => $owner->id,
        ...$attributes,
    ]);
}

function createInteractionActiveResourceCategory(array $attributes = []): Category
{
    return Category::factory()->create([
        'title' => 'Carrier-mediated',
        'type' => Category::TYPE_ACTIVE_INTERACTION,
        ...$attributes,
    ]);
}

function createInteractionActive(User $datasetOwner, array $attributes = []): InteractionActive
{
    $dataset = $attributes['dataset'] ?? createInteractionDataset($datasetOwner);
    $structure = Structure::factory()->create([
        'identifier' => 'MM'.fake()->unique()->numberBetween(1000, 9999),
    ]);
    $protein = Protein::factory()->create([
        'uniprot_id' => 'P'.fake()->unique()->numberBetween(10000, 99999),
    ]);
    $publication = Publication::factory()->create([
        'citation' => 'Interaction publication '.fake()->unique()->numberBetween(100, 999),
    ]);
    $category = createInteractionActiveResourceCategory();

    unset($attributes['dataset']);

    return InteractionActive::query()->create([
        'dataset_id' => $dataset->id,
        'structure_id' => $structure->id,
        'protein_id' => $protein->id,
        'publication_id' => $publication->id,
        'category_id' => $category->id,
        'note' => 'Active transport record.',
        'temperature' => 37,
        'ph' => 7.4,
        'charge' => '+1',
        'km' => 1.2,
        'km_accuracy' => 0.1,
        ...$attributes,
    ]);
}

test('user with dataset view permission can load the active interactions list page', function () {
    $user = createInteractionActiveAdmin([
        PermissionEnums::DATASET_VIEW->value,
    ]);

    $this->actingAs($user);

    Livewire::test(ListInteractionActives::class)
        ->assertOk();
});

test('user without dataset view permission cannot load the active interactions list page', function () {
    $user = createInteractionActiveAdmin();

    $this->actingAs($user);

    Livewire::test(ListInteractionActives::class)
        ->assertForbidden();
});

test('user with dataset view own permission sees only interactions from own datasets', function () {
    $user = createInteractionActiveAdmin([
        PermissionEnums::DATASET_VIEW_OWN->value,
    ]);
    $otherUser = createInteractionActiveAdmin();

    $ownInteraction = createInteractionActive($user, [
        'note' => 'Own active interaction',
    ]);
    $foreignInteraction = createInteractionActive($otherUser, [
        'note' => 'Foreign active interaction',
    ]);

    $this->actingAs($user);

    Livewire::test(ListInteractionActives::class)
        ->assertOk()
        ->assertSee($ownInteraction->note)
        ->assertDontSee($foreignInteraction->note);
});

test('active interactions list page displays existing interaction data for authorized user', function () {
    $user = createInteractionActiveAdmin([
        PermissionEnums::DATASET_VIEW->value,
    ]);
    $interaction = createInteractionActive($user, [
        'note' => 'Transported by carrier',
    ]);

    $this->actingAs($user);

    Livewire::test(ListInteractionActives::class)
        ->assertOk()
        ->assertSee($interaction->structure->identifier)
        ->assertSee($interaction->protein->uniprot_id)
        ->assertSee($interaction->note);
});

// test('user with dataset edit own permission can load the create active interaction page', function () {
//     $user = createInteractionActiveAdmin([
//         PermissionEnums::DATASET_VIEW_OWN->value,
//         PermissionEnums::DATASET_EDIT_OWN->value,
//     ]);

//     $this->actingAs($user);

//     Livewire::test(CreateInteractionActive::class)
//         ->assertOk();
// });

// test('user without dataset edit permission cannot load the create active interaction page', function () {
//     $user = createInteractionActiveAdmin([
//         PermissionEnums::DATASET_VIEW_OWN->value,
//     ]);

//     $this->actingAs($user);

//     Livewire::test(CreateInteractionActive::class)
//         ->assertForbidden();
// });

test('user with dataset edit own permission can load the edit page for interaction in own dataset', function () {
    $user = createInteractionActiveAdmin([
        PermissionEnums::DATASET_VIEW_OWN->value,
        PermissionEnums::DATASET_EDIT_OWN->value,
    ]);
    $interaction = createInteractionActive($user);

    $this->actingAs($user);

    Livewire::test(EditInteractionActive::class, [
        'record' => $interaction->getKey(),
    ])
        ->assertOk()
        ->assertFormSet([
            'dataset_id' => $interaction->dataset_id,
            'structure_id' => $interaction->structure_id,
            'protein_id' => $interaction->protein_id,
            'publication_id' => $interaction->publication_id,
            'category_id' => $interaction->category_id,
            'note' => $interaction->note,
        ]);
});

test('user with dataset edit own permission gets not found for interaction in foreign dataset when only own datasets are visible', function () {
    $user = createInteractionActiveAdmin([
        PermissionEnums::DATASET_VIEW_OWN->value,
        PermissionEnums::DATASET_EDIT_OWN->value,
    ]);
    $owner = createInteractionActiveAdmin();
    $interaction = createInteractionActive($owner);

    $this->actingAs($user);

    $this->get(InteractionActiveResource::getUrl('edit', ['record' => $interaction]))
        ->assertNotFound();
});

test('user with dataset view permission and dataset edit own permission cannot load the edit page for foreign interaction', function () {
    $user = createInteractionActiveAdmin([
        PermissionEnums::DATASET_VIEW->value,
        PermissionEnums::DATASET_EDIT_OWN->value,
    ]);
    $owner = createInteractionActiveAdmin();
    $interaction = createInteractionActive($owner);

    $this->actingAs($user);

    $this->get(InteractionActiveResource::getUrl('edit', ['record' => $interaction]))
        ->assertForbidden();
});

test('interaction active policy delegates dataset permissions for view update and delete', function () {
    $user = createInteractionActiveAdmin([
        PermissionEnums::DATASET_VIEW_OWN->value,
        PermissionEnums::DATASET_EDIT_OWN->value,
        PermissionEnums::DATASET_DELETE_OWN->value,
    ]);
    $interaction = createInteractionActive($user);
    $foreignOwner = createInteractionActiveAdmin();
    $foreignInteraction = createInteractionActive($foreignOwner);

    expect($user->can('view', $interaction))->toBeTrue()
        ->and($user->can('update', $interaction))->toBeTrue()
        ->and($user->can('delete', $interaction))->toBeTrue()
        ->and($user->can('view', $foreignInteraction))->toBeFalse()
        ->and($user->can('update', $foreignInteraction))->toBeFalse()
        ->and($user->can('delete', $foreignInteraction))->toBeFalse();
});

test('interaction active resource exposes index and edit pages', function () {
    expect(InteractionActiveResource::getPages())
        ->toHaveKeys(['index', 'edit']);
});
