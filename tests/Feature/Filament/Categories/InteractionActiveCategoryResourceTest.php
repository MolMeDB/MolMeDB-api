<?php

use App\Enums\PermissionEnums;
use App\Enums\RoleEnums;
use App\Filament\Clusters\Categories\Resources\InteractionActiveCategories\InteractionActiveCategoryResource;
use App\Filament\Clusters\Categories\Resources\InteractionActiveCategories\Pages\CreateInteractionActiveCategory;
use App\Filament\Clusters\Categories\Resources\InteractionActiveCategories\Pages\EditInteractionActiveCategory;
use App\Filament\Clusters\Categories\Resources\InteractionActiveCategories\Pages\InteractionActiveCategoryTree;
use App\Filament\Clusters\Categories\Resources\InteractionActiveCategories\Pages\ListInteractionActiveCategories;
use App\Models\Category;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
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

function createInteractionActiveCategoryAdmin(array $permissions = []): User
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

function createInteractionActiveCategory(array $attributes = []): Category
{
    return Category::factory()->create([
        'type' => Category::TYPE_ACTIVE_INTERACTION,
        ...$attributes,
    ]);
}

test('authorized admin can load the active interaction categories list page', function () {
    $user = createInteractionActiveCategoryAdmin([
        PermissionEnums::CATEGORIES_VIEW->value,
    ]);

    $this->actingAs($user);

    Livewire::test(ListInteractionActiveCategories::class)
        ->assertOk();
});

test('admin without categories view permission cannot load the active interaction categories list page', function () {
    $user = createInteractionActiveCategoryAdmin();

    $this->actingAs($user);

    Livewire::test(ListInteractionActiveCategories::class)
        ->assertForbidden();
});

test('admin with categories permissions can load the create active interaction category page', function () {
    $user = createInteractionActiveCategoryAdmin([
        PermissionEnums::CATEGORIES_VIEW->value,
        PermissionEnums::CATEGORIES_MANAGE->value,
    ]);

    $this->actingAs($user);

    Livewire::test(CreateInteractionActiveCategory::class)
        ->assertOk();
});

test('admin without categories manage permission cannot load the create active interaction category page', function () {
    $user = createInteractionActiveCategoryAdmin([
        PermissionEnums::CATEGORIES_VIEW->value,
    ]);

    $this->actingAs($user);

    Livewire::test(CreateInteractionActiveCategory::class)
        ->assertForbidden();
});

test('admin with categories manage permission can create an active interaction category', function () {
    $user = createInteractionActiveCategoryAdmin([
        PermissionEnums::CATEGORIES_VIEW->value,
        PermissionEnums::CATEGORIES_MANAGE->value,
    ]);

    $this->actingAs($user);

    Livewire::test(CreateInteractionActiveCategory::class)
        ->fillForm([
            'title' => 'Electrostatic',
        ])
        ->call('create')
        ->assertNotified()
        ->assertRedirect();

    expect(Category::query()->where('title', 'Electrostatic')->where('type', Category::TYPE_ACTIVE_INTERACTION)->exists())->toBeTrue();
});

test('admin with categories permissions can load the edit active interaction category page', function () {
    $user = createInteractionActiveCategoryAdmin([
        PermissionEnums::CATEGORIES_VIEW->value,
        PermissionEnums::CATEGORIES_MANAGE->value,
    ]);

    $category = createInteractionActiveCategory();

    $this->actingAs($user);

    Livewire::test(EditInteractionActiveCategory::class, [
        'record' => $category->getKey(),
    ])
        ->assertOk()
        ->assertFormSet([
            'title' => $category->title,
            'type' => Category::TYPE_ACTIVE_INTERACTION,
        ]);
});

test('admin without categories manage permission cannot load the edit active interaction category page', function () {
    $user = createInteractionActiveCategoryAdmin([
        PermissionEnums::CATEGORIES_VIEW->value,
    ]);

    $category = createInteractionActiveCategory();

    $this->actingAs($user);

    Livewire::test(EditInteractionActiveCategory::class, [
        'record' => $category->getKey(),
    ])
        ->assertForbidden();
});

test('admin with categories manage permission can update an active interaction category', function () {
    $user = createInteractionActiveCategoryAdmin([
        PermissionEnums::CATEGORIES_VIEW->value,
        PermissionEnums::CATEGORIES_MANAGE->value,
    ]);

    $category = createInteractionActiveCategory(['title' => 'Old Interaction']);

    $this->actingAs($user);

    Livewire::test(EditInteractionActiveCategory::class, [
        'record' => $category->getKey(),
    ])
        ->fillForm([
            'title' => 'New Interaction',
            'type' => Category::TYPE_ACTIVE_INTERACTION,
        ])
        ->call('save')
        ->assertNotified();

    expect($category->fresh()->title)->toBe('New Interaction');
});

test('admin with categories permissions can load the active interaction category tree page', function () {
    $user = createInteractionActiveCategoryAdmin([
        PermissionEnums::CATEGORIES_VIEW->value,
        PermissionEnums::CATEGORIES_MANAGE->value,
    ]);

    $this->actingAs($user);

    Livewire::test(InteractionActiveCategoryTree::class)
        ->assertOk();
});

test('active interaction category resource exposes index create edit and category tree pages', function () {
    expect(InteractionActiveCategoryResource::getPages())
        ->toHaveKeys(['index', 'create', 'edit', 'categoryTree']);
});
