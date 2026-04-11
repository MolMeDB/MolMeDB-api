<?php

use App\Enums\PermissionEnums;
use App\Enums\RoleEnums;
use App\Filament\Clusters\Categories\Resources\MethodCategories\MethodCategoryResource;
use App\Filament\Clusters\Categories\Resources\MethodCategories\Pages\CreateMethodCategory;
use App\Filament\Clusters\Categories\Resources\MethodCategories\Pages\EditMethodCategory;
use App\Filament\Clusters\Categories\Resources\MethodCategories\Pages\ListMethodCategories;
use App\Filament\Clusters\Categories\Resources\MethodCategories\Pages\MethodCategoryTree;
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

function createCategoryAdmin(array $permissions = []): User
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

function createMethodCategory(array $attributes = []): Category
{
    return Category::factory()->create([
        'type' => Category::TYPE_METHOD,
        ...$attributes,
    ]);
}

test('authorized admin can load the method categories list page', function () {
    $user = createCategoryAdmin([
        PermissionEnums::CATEGORIES_VIEW->value,
    ]);

    $this->actingAs($user);

    Livewire::test(ListMethodCategories::class)
        ->assertOk();
});

test('admin without categories view permission cannot load the method categories list page', function () {
    $user = createCategoryAdmin();

    $this->actingAs($user);

    Livewire::test(ListMethodCategories::class)
        ->assertForbidden();
});

test('method categories list page displays existing method categories only', function () {
    $user = createCategoryAdmin([
        PermissionEnums::CATEGORIES_VIEW->value,
    ]);

    $visibleCategory = createMethodCategory(['title' => 'Chromatography']);
    $hiddenCategory = Category::factory()->create([
        'title' => 'Transmembrane',
        'type' => Category::TYPE_MEMBRANE,
    ]);

    $this->actingAs($user);

    Livewire::test(ListMethodCategories::class)
        ->assertOk()
        ->assertSee($visibleCategory->title)
        ->assertDontSee($hiddenCategory->title);
});

test('admin with categories permissions can load the create method category page', function () {
    $user = createCategoryAdmin([
        PermissionEnums::CATEGORIES_VIEW->value,
        PermissionEnums::CATEGORIES_MANAGE->value,
    ]);

    $this->actingAs($user);

    Livewire::test(CreateMethodCategory::class)
        ->assertOk();
});

test('admin without categories manage permission cannot load the create method category page', function () {
    $user = createCategoryAdmin([
        PermissionEnums::CATEGORIES_VIEW->value,
    ]);

    $this->actingAs($user);

    Livewire::test(CreateMethodCategory::class)
        ->assertForbidden();
});

test('admin with categories manage permission can create a method category', function () {
    $user = createCategoryAdmin([
        PermissionEnums::CATEGORIES_VIEW->value,
        PermissionEnums::CATEGORIES_MANAGE->value,
    ]);

    $this->actingAs($user);

    Livewire::test(CreateMethodCategory::class)
        ->fillForm([
            'title' => 'Machine Learning',
        ])
        ->call('create')
        ->assertNotified()
        ->assertRedirect();

    expect(Category::query()->where('title', 'Machine Learning')->where('type', Category::TYPE_METHOD)->exists())->toBeTrue();
});

test('admin with categories permissions can load the edit method category page', function () {
    $user = createCategoryAdmin([
        PermissionEnums::CATEGORIES_VIEW->value,
        PermissionEnums::CATEGORIES_MANAGE->value,
    ]);

    $category = createMethodCategory();

    $this->actingAs($user);

    Livewire::test(EditMethodCategory::class, [
        'record' => $category->getKey(),
    ])
        ->assertOk()
        ->assertFormSet([
            'title' => $category->title,
            'type' => Category::TYPE_METHOD,
        ]);
});

test('admin without categories manage permission cannot load the edit method category page', function () {
    $user = createCategoryAdmin([
        PermissionEnums::CATEGORIES_VIEW->value,
    ]);

    $category = createMethodCategory();

    $this->actingAs($user);

    Livewire::test(EditMethodCategory::class, [
        'record' => $category->getKey(),
    ])
        ->assertForbidden();
});

test('admin with categories manage permission can update a method category', function () {
    $user = createCategoryAdmin([
        PermissionEnums::CATEGORIES_VIEW->value,
        PermissionEnums::CATEGORIES_MANAGE->value,
    ]);

    $category = createMethodCategory(['title' => 'Old Method']);

    $this->actingAs($user);

    Livewire::test(EditMethodCategory::class, [
        'record' => $category->getKey(),
    ])
        ->fillForm([
            'title' => 'New Method',
            'type' => Category::TYPE_METHOD,
        ])
        ->call('save')
        ->assertNotified();

    expect($category->fresh()->title)->toBe('New Method');
});

test('admin with categories permissions can load the method category tree page', function () {
    $user = createCategoryAdmin([
        PermissionEnums::CATEGORIES_VIEW->value,
        PermissionEnums::CATEGORIES_MANAGE->value,
    ]);

    $this->actingAs($user);

    Livewire::test(MethodCategoryTree::class)
        ->assertOk();
});

test('method category resource exposes index create edit and category tree pages', function () {
    expect(MethodCategoryResource::getPages())
        ->toHaveKeys(['index', 'create', 'edit_record', 'categoryTree']);
});
