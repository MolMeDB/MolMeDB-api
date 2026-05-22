<?php

use App\Enums\PermissionEnums;
use App\Enums\RoleEnums;
use App\Filament\Clusters\Access\Resources\Roles\Pages\CreateRole;
use App\Filament\Clusters\Access\Resources\Roles\Pages\EditRole;
use App\Filament\Clusters\Access\Resources\Roles\Pages\ListRoles;
use App\Filament\Clusters\Access\Resources\Roles\RoleResource;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Filament\Forms\Components\TextInput;
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

function createRoleAdmin(array $permissions = []): User
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

test('authorized admin can load the roles list page', function () {
    $user = createRoleAdmin([
        PermissionEnums::ROLES_VIEW->value,
    ]);

    $this->actingAs($user);

    Livewire::test(ListRoles::class)
        ->assertOk();
});

test('admin without roles view permission cannot load the roles list page', function () {
    $user = createRoleAdmin();

    $this->actingAs($user);

    Livewire::test(ListRoles::class)
        ->assertForbidden();
});

test('roles list page displays existing roles', function () {
    $user = createRoleAdmin([
        PermissionEnums::ROLES_VIEW->value,
    ]);

    $role = Role::query()->create([
        'name' => 'Quality Assurance',
        'guard_name' => 'web',
    ]);

    $this->actingAs($user);

    Livewire::test(ListRoles::class)
        ->assertOk()
        ->assertSee($role->name);
});

test('admin with roles edit permission can load the create role page', function () {
    $user = createRoleAdmin([
        PermissionEnums::ROLES_VIEW->value,
        PermissionEnums::ROLES_EDIT->value,
    ]);

    $this->actingAs($user);

    Livewire::test(CreateRole::class)
        ->assertOk();
});

test('admin without roles edit permission cannot load the create role page', function () {
    $user = createRoleAdmin([
        PermissionEnums::ROLES_VIEW->value,
    ]);

    $this->actingAs($user);

    Livewire::test(CreateRole::class)
        ->assertForbidden();
});

test('admin with roles edit permission can create a role', function () {
    $user = createRoleAdmin([
        PermissionEnums::ROLES_VIEW->value,
        PermissionEnums::ROLES_EDIT->value,
    ]);

    $this->actingAs($user);

    Livewire::test(CreateRole::class)
        ->fillForm([
            'name' => 'Data Curator',
        ])
        ->call('create')
        ->assertNotified()
        ->assertRedirect();

    expect(Role::query()->where('name', 'Data Curator')->exists())->toBeTrue();
});

test('admin with roles edit permission can load the edit role page', function () {
    $user = createRoleAdmin([
        PermissionEnums::ROLES_VIEW->value,
        PermissionEnums::ROLES_EDIT->value,
    ]);

    $role = Role::query()->create([
        'name' => 'Import Specialist',
        'guard_name' => 'web',
    ]);

    $this->actingAs($user);

    Livewire::test(EditRole::class, [
        'record' => $role->getKey(),
    ])
        ->assertOk()
        ->assertFormSet([
            'name' => $role->name,
        ]);
});

test('admin without roles edit permission cannot load the edit role page', function () {
    $user = createRoleAdmin([
        PermissionEnums::ROLES_VIEW->value,
    ]);

    $role = Role::query()->create([
        'name' => 'Import Specialist',
        'guard_name' => 'web',
    ]);

    $this->actingAs($user);

    Livewire::test(EditRole::class, [
        'record' => $role->getKey(),
    ])
        ->assertForbidden();
});

test('default role name field is disabled on edit page', function () {
    $user = createRoleAdmin([
        PermissionEnums::ROLES_VIEW->value,
        PermissionEnums::ROLES_EDIT->value,
    ]);

    $defaultRole = Role::query()->where('name', RoleEnums::ADMIN->value)->firstOrFail();

    $this->actingAs($user);

    Livewire::test(EditRole::class, [
        'record' => $defaultRole->getKey(),
    ])
        ->assertOk()
        ->assertFormFieldExists('name', function (TextInput $field): bool {
            return $field->isDisabled();
        });
});

test('role resource exposes index create and edit pages', function () {
    expect(RoleResource::getPages())
        ->toHaveKeys(['index', 'create', 'edit']);
});
