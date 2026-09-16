<?php

use App\Enums\PermissionEnums;
use App\Enums\RoleEnums;
use App\Filament\Clusters\Access\Resources\Permissions\Pages\ListPermissions;
use App\Filament\Clusters\Access\Resources\Permissions\PermissionResource;
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

    Role::query()->firstOrCreate([
        'name' => RoleEnums::VIEWER->value,
        'guard_name' => 'web',
    ]);

    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

function createPermissionAdmin(?Permission $permission = null, bool $canViewPermissions = true): User
{
    $adminRole = Role::query()->firstOrCreate([
        'name' => RoleEnums::ADMIN->value,
        'guard_name' => 'web',
    ]);

    $user = User::factory()->create();
    $user->syncRoles([$adminRole]);

    if ($canViewPermissions) {
        $adminRole->givePermissionTo(
            Permission::query()->firstOrCreate([
                'name' => PermissionEnums::ROLES_VIEW->value,
                'guard_name' => 'web',
            ], [
                'description' => PermissionEnums::ROLES_VIEW->description(),
            ]),
        );
    }

    if ($permission) {
        $user->givePermissionTo($permission);
    }

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    return $user->refresh();
}

test('authorized admin can load the permissions list page', function () {
    $user = createPermissionAdmin();

    $this->actingAs($user);

    Livewire::test(ListPermissions::class)
        ->assertOk();
});

test('admin without roles view permission cannot load the permissions list page', function () {
    $user = createPermissionAdmin(canViewPermissions: false);

    $this->actingAs($user);

    Livewire::test(ListPermissions::class)
        ->assertForbidden();
});

test('permissions list page displays existing permissions', function () {
    $user = createPermissionAdmin();

    $permission = Permission::query()->create([
        'name' => 'permissions.test.unique',
        'guard_name' => 'web',
        'description' => 'Permission test description',
    ]);

    $this->actingAs($user);

    Livewire::test(ListPermissions::class)
        ->assertOk()
        ->loadTable()
        ->searchTable($permission->name)
        ->assertCanSeeTableRecords([$permission])
        ->assertSee($permission->description);
});

test('permission resource exposes only the index page', function () {
    expect(PermissionResource::getPages())
        ->toHaveKey('index')
        ->not->toHaveKey('create')
        ->not->toHaveKey('edit');
});

test('user can view a permission when they have the matching direct permission', function () {
    $targetPermission = Permission::query()->firstOrCreate([
        'name' => PermissionEnums::SETTINGS_VIEW->value,
        'guard_name' => 'web',
    ], [
        'description' => PermissionEnums::SETTINGS_VIEW->description(),
    ]);

    $user = createPermissionAdmin(permission: $targetPermission, canViewPermissions: false);

    expect($user->can('view', $targetPermission))->toBeTrue();
});

test('permission policy denies create update and delete actions', function () {
    $permission = Permission::query()->firstOrCreate([
        'name' => PermissionEnums::SETTINGS_EDIT->value,
        'guard_name' => 'web',
    ], [
        'description' => PermissionEnums::SETTINGS_EDIT->description(),
    ]);

    $user = createPermissionAdmin();

    expect($user->can('create', Permission::class))->toBeFalse()
        ->and($user->can('update', $permission))->toBeFalse()
        ->and($user->can('delete', $permission))->toBeFalse();
});
