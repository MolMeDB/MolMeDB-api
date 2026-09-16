<?php

use App\Enums\PermissionEnums;
use App\Enums\RoleEnums;
use App\Filament\Clusters\Categories\Resources\MembraneCategories\MembraneCategoryResource;
use App\Filament\Clusters\Categories\Resources\MembraneCategories\Pages\CreateMembraneCategory;
use App\Filament\Clusters\Categories\Resources\MembraneCategories\Pages\EditMembraneCategory;
use App\Filament\Clusters\Categories\Resources\MembraneCategories\Pages\ListMembraneCategories;
use App\Filament\Clusters\Categories\Resources\MembraneCategories\Pages\MembraneCategoryTree;
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

function createMembraneCategoryAdmin(array $permissions = []): User
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

function createMembraneCategory(array $attributes = []): Category
{
    return Category::factory()->create([
        'type' => Category::TYPE_MEMBRANE,
        ...$attributes,
    ]);
}

test('authorized admin can load the membrane categories list page', function () {
    $user = createMembraneCategoryAdmin([
        PermissionEnums::CATEGORIES_VIEW->value,
    ]);

    $this->actingAs($user);

    Livewire::test(ListMembraneCategories::class)
        ->assertOk();
});

test('admin without categories view permission cannot load the membrane categories list page', function () {
    $user = createMembraneCategoryAdmin();

    $this->actingAs($user);

    Livewire::test(ListMembraneCategories::class)
        ->assertForbidden();
});

test('membrane categories list page displays existing membrane categories only', function () {
    $user = createMembraneCategoryAdmin([
        PermissionEnums::CATEGORIES_VIEW->value,
    ]);

    $visibleCategory = createMembraneCategory(['title' => 'Bilayer']);
    $hiddenCategory = Category::factory()->create([
        'title' => 'Enzymatic',
        'type' => Category::TYPE_METHOD,
    ]);

    $this->actingAs($user);

    Livewire::test(ListMembraneCategories::class)
        ->assertOk()
        ->assertSee($visibleCategory->title)
        ->assertDontSee($hiddenCategory->title);
});

test('admin with categories permissions can load the create membrane category page', function () {
    $user = createMembraneCategoryAdmin([
        PermissionEnums::CATEGORIES_VIEW->value,
        PermissionEnums::CATEGORIES_MANAGE->value,
    ]);

    $this->actingAs($user);

    Livewire::test(CreateMembraneCategory::class)
        ->assertOk();
});

test('admin without categories manage permission cannot load the create membrane category page', function () {
    $user = createMembraneCategoryAdmin([
        PermissionEnums::CATEGORIES_VIEW->value,
    ]);

    $this->actingAs($user);

    Livewire::test(CreateMembraneCategory::class)
        ->assertForbidden();
});

test('admin with categories manage permission can create a membrane category', function () {
    $user = createMembraneCategoryAdmin([
        PermissionEnums::CATEGORIES_VIEW->value,
        PermissionEnums::CATEGORIES_MANAGE->value,
    ]);

    $this->actingAs($user);

    Livewire::test(CreateMembraneCategory::class)
        ->fillForm([
            'title' => 'Lipid Raft',
        ])
        ->call('create')
        ->assertNotified()
        ->assertRedirect();

    expect(Category::query()->where('title', 'Lipid Raft')->where('type', Category::TYPE_MEMBRANE)->exists())->toBeTrue();
});

test('admin with categories permissions can load the edit membrane category page', function () {
    $user = createMembraneCategoryAdmin([
        PermissionEnums::CATEGORIES_VIEW->value,
        PermissionEnums::CATEGORIES_MANAGE->value,
    ]);

    $category = createMembraneCategory();

    $this->actingAs($user);

    Livewire::test(EditMembraneCategory::class, [
        'record' => $category->getKey(),
    ])
        ->assertOk()
        ->assertFormSet([
            'title' => $category->title,
            'type' => Category::TYPE_MEMBRANE,
        ]);
});

test('admin without categories manage permission cannot load the edit membrane category page', function () {
    $user = createMembraneCategoryAdmin([
        PermissionEnums::CATEGORIES_VIEW->value,
    ]);

    $category = createMembraneCategory();

    $this->actingAs($user);

    Livewire::test(EditMembraneCategory::class, [
        'record' => $category->getKey(),
    ])
        ->assertForbidden();
});

test('admin with categories manage permission can update a membrane category', function () {
    $user = createMembraneCategoryAdmin([
        PermissionEnums::CATEGORIES_VIEW->value,
        PermissionEnums::CATEGORIES_MANAGE->value,
    ]);

    $category = createMembraneCategory(['title' => 'Old Membrane']);

    $this->actingAs($user);

    Livewire::test(EditMembraneCategory::class, [
        'record' => $category->getKey(),
    ])
        ->fillForm([
            'title' => 'New Membrane',
            'type' => Category::TYPE_MEMBRANE,
        ])
        ->call('save')
        ->assertNotified();

    expect($category->fresh()->title)->toBe('New Membrane');
});

test('admin with categories permissions can load the membrane category tree page', function () {
    $user = createMembraneCategoryAdmin([
        PermissionEnums::CATEGORIES_VIEW->value,
        PermissionEnums::CATEGORIES_MANAGE->value,
    ]);

    $this->actingAs($user);

    Livewire::test(MembraneCategoryTree::class)
        ->assertOk();
});

test('membrane category resource exposes index create edit and category tree pages', function () {
    expect(MembraneCategoryResource::getPages())
        ->toHaveKeys(['index', 'create', 'edit_record', 'categoryTree']);
});
