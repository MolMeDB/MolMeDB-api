<?php

use App\Enums\PermissionEnums;
use App\Enums\RoleEnums;
use App\Filament\Clusters\Settings\Resources\SshCredentials\Pages\CreateSshCredential;
use App\Filament\Clusters\Settings\Resources\SshCredentials\Pages\EditSshCredential;
use App\Filament\Clusters\Settings\Resources\SshCredentials\Pages\ListSshCredentials;
use App\Filament\Clusters\Settings\Resources\SshCredentials\Pages\SshCredentialActivities;
use App\Filament\Clusters\Settings\Resources\SshCredentials\SshCredentialResource;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SshCredential;
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

function createSettingsAdmin(): User
{
    $adminRole = Role::query()->firstOrCreate([
        'name' => RoleEnums::ADMIN->value,
        'guard_name' => 'web',
    ]);

    $user = User::factory()->create();
    $user->syncRoles([$adminRole]);

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    return $user->refresh();
}

function createSshCredentialManager(): User
{
    $user = createSettingsAdmin();

    $adminRole = Role::query()->where('name', RoleEnums::ADMIN->value)->firstOrFail();
    $adminRole->givePermissionTo(
        Permission::query()->where('name', PermissionEnums::SSH_CREDENTIALS_MANAGE->value)->firstOrFail(),
    );

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    return $user->refresh();
}

function createSshCredential(array $attributes = []): SshCredential
{
    return SshCredential::query()->create([
        'name' => 'Backup Access',
        'username' => 'deploy',
        'type' => SshCredential::AUTH_TYPE_KEY,
        'private_key' => str_repeat('k', 40),
        'passphrase' => 'secret-passphrase',
        ...$attributes,
    ]);
}

test('admin can load the ssh credentials list page', function () {
    $user = createSshCredentialManager();

    $this->actingAs($user);

    Livewire::test(ListSshCredentials::class)
        ->assertOk();
});

test('ssh credentials list page displays existing credentials', function () {
    $user = createSshCredentialManager();
    $credential = createSshCredential([
        'name' => 'Production Access',
        'username' => 'prod-user',
        'type' => SshCredential::AUTH_TYPE_PASSWORD,
    ]);

    $this->actingAs($user);

    Livewire::test(ListSshCredentials::class)
        ->assertOk()
        ->assertSee($credential->name)
        ->assertSee(SshCredential::types()[$credential->type]);
});

test('admin can load the create ssh credential page', function () {
    $user = createSshCredentialManager();

    $this->actingAs($user);

    Livewire::test(CreateSshCredential::class)
        ->assertOk();
});

test('admin can create an ssh credential with key based authentication', function () {
    $user = createSshCredentialManager();

    $this->actingAs($user);

    Livewire::test(CreateSshCredential::class)
        ->fillForm([
            'name' => 'MetaCentrum Key',
            'type' => SshCredential::AUTH_TYPE_KEY,
            'username' => 'meta-user',
            'password' => null,
            'private_key' => str_repeat('a', 64),
            'passphrase' => 'meta-secret',
        ])
        ->call('create')
        ->assertNotified()
        ->assertRedirect();

    $credential = SshCredential::query()
        ->where('name', 'MetaCentrum Key')
        ->first();

    expect($credential)->not->toBeNull()
        ->and($credential->username)->toBe('meta-user')
        ->and($credential->type)->toBe(SshCredential::AUTH_TYPE_KEY)
        ->and($credential->created_by)->toBe($user->id);
});

test('admin can create an ssh credential with password authentication', function () {
    $user = createSshCredentialManager();

    $this->actingAs($user);

    Livewire::test(CreateSshCredential::class)
        ->fillForm([
            'name' => 'Legacy Access',
            'type' => SshCredential::AUTH_TYPE_PASSWORD,
            'username' => 'legacy-user',
            'password' => 'legacy-secret',
            'private_key' => null,
            'passphrase' => null,
        ])
        ->call('create')
        ->assertNotified()
        ->assertRedirect();

    expect(
        SshCredential::query()
            ->where('name', 'Legacy Access')
            ->where('type', SshCredential::AUTH_TYPE_PASSWORD)
            ->exists()
    )->toBeTrue();
});

test('admin can load the edit ssh credential page', function () {
    $user = createSshCredentialManager();
    $credential = createSshCredential();

    $this->actingAs($user);

    Livewire::test(EditSshCredential::class, [
        'record' => $credential->getKey(),
    ])
        ->assertOk()
        ->assertFormSet([
            'name' => $credential->name,
            'username' => $credential->username,
            'type' => $credential->type,
        ]);
});

test('admin can update an ssh credential', function () {
    $user = createSshCredentialManager();
    $credential = createSshCredential([
        'name' => 'Old Access',
        'username' => 'old-user',
        'type' => SshCredential::AUTH_TYPE_PASSWORD,
        'password' => 'old-secret',
    ]);

    $this->actingAs($user);

    Livewire::test(EditSshCredential::class, [
        'record' => $credential->getKey(),
    ])
        ->fillForm([
            'name' => 'New Access',
            'type' => SshCredential::AUTH_TYPE_PASSWORD,
            'username' => 'new-user',
            'password' => 'new-secret',
            'private_key' => null,
            'passphrase' => null,
        ])
        ->call('save')
        ->assertNotified();

    expect($credential->fresh())
        ->name->toBe('New Access')
        ->username->toBe('new-user')
        ->type->toBe(SshCredential::AUTH_TYPE_PASSWORD);
});

test('admin can load the ssh credential activities page', function () {
    $user = createSshCredentialManager();
    $credential = createSshCredential();

    $this->actingAs($user);

    Livewire::test(SshCredentialActivities::class, [
        'record' => $credential->getKey(),
    ])
        ->assertOk();
});

test('ssh credential resource exposes index create edit and activities pages', function () {
    expect(SshCredentialResource::getPages())
        ->toHaveKeys(['index', 'create', 'edit', 'activities']);
});

test('admin without ssh credentials manage permission cannot load the ssh credentials list page', function () {
    $user = createSettingsAdmin();

    $this->actingAs($user);

    Livewire::test(ListSshCredentials::class)
        ->assertForbidden();
});

test('admin without ssh credentials manage permission cannot load the create ssh credential page', function () {
    $user = createSettingsAdmin();

    $this->actingAs($user);

    Livewire::test(CreateSshCredential::class)
        ->assertForbidden();
});

test('admin without ssh credentials manage permission cannot load the edit ssh credential page', function () {
    $user = createSettingsAdmin();
    $credential = createSshCredential();

    $this->actingAs($user);

    Livewire::test(EditSshCredential::class, [
        'record' => $credential->getKey(),
    ])
        ->assertForbidden();
});

test('admin without ssh credentials manage permission cannot load the ssh credential activities page', function () {
    $user = createSettingsAdmin();
    $credential = createSshCredential();

    $this->actingAs($user);

    Livewire::test(SshCredentialActivities::class, [
        'record' => $credential->getKey(),
    ])
        ->assertForbidden();
});
