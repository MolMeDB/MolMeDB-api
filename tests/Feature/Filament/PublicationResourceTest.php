<?php

use App\Enums\PermissionEnums;
use App\Enums\RoleEnums;
use App\Filament\Resources\Publications\Pages\CreatePublication;
use App\Filament\Resources\Publications\Pages\EditPublication;
use App\Filament\Resources\Publications\Pages\ListPublications;
use App\Filament\Resources\Publications\PublicationResource;
use App\Models\Permission;
use App\Models\Publication;
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

function createPublicationAdmin(array $permissions = []): User
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

function createPublication(array $attributes = []): Publication
{
    return Publication::factory()->create([
        'citation' => 'Example citation for membrane transport study.',
        'doi' => null,
        'identifier' => null,
        'identifier_source' => null,
        'title' => null,
        'journal' => null,
        'volume' => null,
        'issue' => null,
        'page' => null,
        'year' => null,
        'published_at' => null,
        'validated_at' => null,
        ...$attributes,
    ]);
}

test('user with publication view permission can load the publications list page', function () {
    $user = createPublicationAdmin([
        PermissionEnums::PUBLICATION_VIEW->value,
    ]);

    $this->actingAs($user);

    Livewire::test(ListPublications::class)
        ->assertOk();
});

test('user without publication view permission cannot load the publications list page', function () {
    $user = createPublicationAdmin();

    $this->actingAs($user);

    Livewire::test(ListPublications::class)
        ->assertForbidden();
});

test('publications list page displays existing publications for authorized user', function () {
    $user = createPublicationAdmin([
        PermissionEnums::PUBLICATION_VIEW->value,
    ]);
    $publication = createPublication([
        'citation' => 'Curated citation for publication resource tests.',
    ]);

    $this->actingAs($user);

    Livewire::test(ListPublications::class)
        ->assertOk()
        ->assertSee($publication->citation);
});

test('user with publication view and edit permission can load the create publication page', function () {
    $user = createPublicationAdmin([
        PermissionEnums::PUBLICATION_VIEW->value,
        PermissionEnums::PUBLICATION_EDIT->value,
    ]);

    $this->actingAs($user);

    Livewire::test(CreatePublication::class)
        ->assertOk()
        ->assertFormFieldExists('citation')
        ->assertFormFieldExists('identifier')
        ->assertFormFieldExists('doi');
});

test('user without publication edit permission cannot load the create publication page', function () {
    $user = createPublicationAdmin([
        PermissionEnums::PUBLICATION_VIEW->value,
    ]);

    $this->actingAs($user);

    Livewire::test(CreatePublication::class)
        ->assertForbidden();
});

test('user with publication edit permission can create a local publication without remote lookup', function () {
    $user = createPublicationAdmin([
        PermissionEnums::PUBLICATION_VIEW->value,
        PermissionEnums::PUBLICATION_EDIT->value,
    ]);

    $this->actingAs($user);

    Livewire::test(CreatePublication::class)
        ->fillForm([
            'citation' => 'Local unlinked citation',
            'identifier' => null,
            'identifier_source' => null,
            'doi' => null,
        ])
        ->call('create')
        ->assertNotified()
        ->assertRedirect();

    $publication = Publication::query()
        ->where('citation', 'Local unlinked citation')
        ->first();

    expect($publication)->not->toBeNull()
        ->and($publication->doi)->toBeNull()
        ->and($publication->identifier)->toBeNull();
});

test('user with publication view and edit permission can load the edit publication page', function () {
    $user = createPublicationAdmin([
        PermissionEnums::PUBLICATION_VIEW->value,
        PermissionEnums::PUBLICATION_EDIT->value,
    ]);
    $publication = createPublication([
        'citation' => 'Editable publication',
    ]);

    $this->actingAs($user);

    Livewire::test(EditPublication::class, [
        'record' => $publication->getKey(),
    ])
        ->assertOk()
        ->assertFormSet([
            'citation' => $publication->citation,
            'identifier' => $publication->identifier,
            'identifier_source' => $publication->identifier_source,
            'doi' => $publication->doi,
        ]);
});

test('user without publication edit permission cannot load the edit publication page', function () {
    $user = createPublicationAdmin([
        PermissionEnums::PUBLICATION_VIEW->value,
    ]);
    $publication = createPublication();

    $this->actingAs($user);

    Livewire::test(EditPublication::class, [
        'record' => $publication->getKey(),
    ])
        ->assertForbidden();
});

test('user with publication edit permission can update a local publication without remote lookup', function () {
    $user = createPublicationAdmin([
        PermissionEnums::PUBLICATION_VIEW->value,
        PermissionEnums::PUBLICATION_EDIT->value,
    ]);
    $publication = createPublication([
        'citation' => 'Old local citation',
    ]);

    $this->actingAs($user);

    Livewire::test(EditPublication::class, [
        'record' => $publication->getKey(),
    ])
        ->fillForm([
            'citation' => 'Updated local citation',
            'identifier' => null,
            'identifier_source' => null,
            'doi' => null,
        ])
        ->call('save')
        ->assertNotified();

    $updatedPublication = $publication->fresh();

    expect($updatedPublication)
        ->citation->toBe('Updated local citation')
        ->doi->toBeNull()
        ->identifier->toBeNull();
});

test('publication policy allows global view create update and delete permissions', function () {
    $user = createPublicationAdmin([
        PermissionEnums::PUBLICATION_VIEW->value,
        PermissionEnums::PUBLICATION_EDIT->value,
        PermissionEnums::PUBLICATION_DELETE->value,
    ]);
    $publication = createPublication();

    expect($user->can('view', $publication))->toBeTrue()
        ->and($user->can('create', Publication::class))->toBeTrue()
        ->and($user->can('update', $publication))->toBeTrue()
        ->and($user->can('delete', $publication))->toBeTrue()
        ->and($user->can('restore', $publication))->toBeTrue();
});

test('publication resource exposes index create and edit pages', function () {
    expect(PublicationResource::getPages())
        ->toHaveKeys(['index', 'create', 'edit']);
});
