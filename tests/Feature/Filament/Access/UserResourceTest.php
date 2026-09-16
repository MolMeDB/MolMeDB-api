<?php

use App\Enums\PermissionEnums;
use App\Enums\RoleEnums;
use App\Filament\Clusters\Access\Resources\Users\Pages\EditUser;
use App\Filament\Clusters\Access\Resources\Users\Pages\ListUsers;
use App\Filament\Clusters\Access\Resources\Users\UserResource;
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

function createUserAdmin(array $permissions = []): User
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

test('authorized admin can load the users list page', function () {
    $user = createUserAdmin([
        PermissionEnums::USERS_VIEW->value,
    ]);

    $this->actingAs($user);

    Livewire::test(ListUsers::class)
        ->assertOk();
});

test('admin without users view permission cannot load the users list page', function () {
    $user = createUserAdmin();

    $this->actingAs($user);

    Livewire::test(ListUsers::class)
        ->assertForbidden();
});

test('users list page displays existing users', function () {
    $user = createUserAdmin([
        PermissionEnums::USERS_VIEW->value,
    ]);

    $targetUser = User::factory()->create([
        'name' => 'Jane Curator',
        'email' => 'jane@example.com',
    ]);

    $this->actingAs($user);

    Livewire::test(ListUsers::class)
        ->assertOk()
        ->assertSee($targetUser->name)
        ->assertSee($targetUser->email);
});

test('admin with users edit permission can load another users edit page', function () {
    $user = createUserAdmin([
        PermissionEnums::USERS_VIEW->value,
        PermissionEnums::USERS_EDIT->value,
    ]);

    $targetUser = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(EditUser::class, [
        'record' => $targetUser->getKey(),
    ])
        ->assertOk()
        ->assertFormSet([
            'name' => $targetUser->name,
            'email' => $targetUser->email,
            'affiliation' => $targetUser->affiliation,
        ]);
});

test('admin without users edit permission cannot load another users edit page', function () {
    $user = createUserAdmin([
        PermissionEnums::USERS_VIEW->value,
    ]);

    $targetUser = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(EditUser::class, [
        'record' => $targetUser->getKey(),
    ])
        ->assertForbidden();
});

test('other user profile fields are disabled without elevated edit permission', function () {
    $user = createUserAdmin([
        PermissionEnums::USERS_VIEW->value,
    ]);

    $targetUser = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(EditUser::class, [
        'record' => $targetUser->getKey(),
    ])
        ->assertForbidden();
});

test('user resource exposes only index and edit pages', function () {
    expect(UserResource::getPages())
        ->toHaveKey('index')
        ->toHaveKey('edit')
        ->not->toHaveKey('create');
});
