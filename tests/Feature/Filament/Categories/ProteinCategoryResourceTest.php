<?php

use App\Enums\PermissionEnums;
use App\Enums\RoleEnums;
use App\Filament\Clusters\Categories\Resources\ProteinCategories\Pages\CreateProteinCategory;
use App\Filament\Clusters\Categories\Resources\ProteinCategories\Pages\EditProteinCategory;
use App\Filament\Clusters\Categories\Resources\ProteinCategories\Pages\ListProteinCategories;
use App\Filament\Clusters\Categories\Resources\ProteinCategories\Pages\ProteinCategoryTree;
use App\Filament\Clusters\Categories\Resources\ProteinCategories\ProteinCategoryResource;
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

function createProteinCategoryAdmin(array $permissions = []): User
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

function createProteinCategory(array $attributes = []): Category
{
    return Category::factory()->create([
        'type' => Category::TYPE_PROTEIN,
        ...$attributes,
    ]);
}

test('authorized admin can load the protein categories list page', function () {
    $user = createProteinCategoryAdmin([
        PermissionEnums::CATEGORIES_VIEW->value,
    ]);

    $this->actingAs($user);

    Livewire::test(ListProteinCategories::class)
        ->assertOk();
});

test('admin without categories view permission cannot load the protein categories list page', function () {
    $user = createProteinCategoryAdmin();

    $this->actingAs($user);

    Livewire::test(ListProteinCategories::class)
        ->assertForbidden();
});

test('protein categories list page displays existing protein categories only', function () {
    $user = createProteinCategoryAdmin([
        PermissionEnums::CATEGORIES_VIEW->value,
    ]);

    $visibleCategory = createProteinCategory(['title' => 'GPCR']);
    $hiddenCategory = Category::factory()->create([
        'title' => 'Bilayer',
        'type' => Category::TYPE_MEMBRANE,
    ]);

    $this->actingAs($user);

    Livewire::test(ListProteinCategories::class)
        ->assertOk()
        ->assertSee($visibleCategory->title)
        ->assertDontSee($hiddenCategory->title);
});

test('admin with categories permissions can load the create protein category page', function () {
    $user = createProteinCategoryAdmin([
        PermissionEnums::CATEGORIES_VIEW->value,
        PermissionEnums::CATEGORIES_MANAGE->value,
    ]);

    $this->actingAs($user);

    Livewire::test(CreateProteinCategory::class)
        ->assertOk();
});

test('admin without categories manage permission cannot load the create protein category page', function () {
    $user = createProteinCategoryAdmin([
        PermissionEnums::CATEGORIES_VIEW->value,
    ]);

    $this->actingAs($user);

    Livewire::test(CreateProteinCategory::class)
        ->assertForbidden();
});

test('admin with categories manage permission can create a protein category', function () {
    $user = createProteinCategoryAdmin([
        PermissionEnums::CATEGORIES_VIEW->value,
        PermissionEnums::CATEGORIES_MANAGE->value,
    ]);

    $this->actingAs($user);

    Livewire::test(CreateProteinCategory::class)
        ->fillForm([
            'title' => 'Ion Channel',
        ])
        ->call('create')
        ->assertNotified()
        ->assertRedirect();

    expect(Category::query()->where('title', 'Ion Channel')->where('type', Category::TYPE_PROTEIN)->exists())->toBeTrue();
});

test('admin with categories permissions can load the edit protein category page', function () {
    $user = createProteinCategoryAdmin([
        PermissionEnums::CATEGORIES_VIEW->value,
        PermissionEnums::CATEGORIES_MANAGE->value,
    ]);

    $category = createProteinCategory();

    $this->actingAs($user);

    Livewire::test(EditProteinCategory::class, [
        'record' => $category->getKey(),
    ])
        ->assertOk()
        ->assertFormSet([
            'title' => $category->title,
            'type' => Category::TYPE_PROTEIN,
        ]);
});

test('admin without categories manage permission cannot load the edit protein category page', function () {
    $user = createProteinCategoryAdmin([
        PermissionEnums::CATEGORIES_VIEW->value,
    ]);

    $category = createProteinCategory();

    $this->actingAs($user);

    Livewire::test(EditProteinCategory::class, [
        'record' => $category->getKey(),
    ])
        ->assertForbidden();
});

test('admin with categories manage permission can update a protein category', function () {
    $user = createProteinCategoryAdmin([
        PermissionEnums::CATEGORIES_VIEW->value,
        PermissionEnums::CATEGORIES_MANAGE->value,
    ]);

    $category = createProteinCategory(['title' => 'Old Protein']);

    $this->actingAs($user);

    Livewire::test(EditProteinCategory::class, [
        'record' => $category->getKey(),
    ])
        ->fillForm([
            'title' => 'New Protein',
            'type' => Category::TYPE_PROTEIN,
        ])
        ->call('save')
        ->assertNotified();

    expect($category->fresh()->title)->toBe('New Protein');
});

test('admin with categories permissions can load the protein category tree page', function () {
    $user = createProteinCategoryAdmin([
        PermissionEnums::CATEGORIES_VIEW->value,
        PermissionEnums::CATEGORIES_MANAGE->value,
    ]);

    $this->actingAs($user);

    Livewire::test(ProteinCategoryTree::class)
        ->assertOk();
});

test('protein category resource exposes index create edit and category tree pages', function () {
    expect(ProteinCategoryResource::getPages())
        ->toHaveKeys(['index', 'create', 'edit', 'categoryTree']);
});
