<?php

use App\Enums\PermissionEnums;
use App\Enums\RoleEnums;
use App\Filament\Resources\Proteins\Pages\CreateProtein;
use App\Filament\Resources\Proteins\Pages\EditProtein;
use App\Filament\Resources\Proteins\Pages\ListProteins;
use App\Filament\Resources\Proteins\ProteinResource;
use App\Models\Category;
use App\Models\Permission;
use App\Models\Protein;
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

function createProteinAdmin(array $permissions = []): User
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

function createProteinResourceCategory(array $attributes = []): Category
{
    return Category::factory()->create([
        'title' => 'Transport proteins',
        'type' => Category::TYPE_PROTEIN,
        ...$attributes,
    ]);
}

function createProtein(array $attributes = []): Protein
{
    $protein = Protein::factory()->create([
        'uniprot_id' => 'P12345',
        ...$attributes,
    ]);

    $protein->categories()->syncWithPivotValues(
        [createProteinResourceCategory()->id],
        ['model_type' => Protein::class],
    );

    return $protein->refresh();
}

test('user with protein view permission can load the proteins list page', function () {
    $user = createProteinAdmin([
        PermissionEnums::PROTEIN_VIEW->value,
    ]);

    $this->actingAs($user);

    Livewire::test(ListProteins::class)
        ->assertOk();
});

test('user without protein view permission cannot load the proteins list page', function () {
    $user = createProteinAdmin();

    $this->actingAs($user);

    Livewire::test(ListProteins::class)
        ->assertForbidden();
});

test('proteins list page displays existing proteins for authorized user', function () {
    $user = createProteinAdmin([
        PermissionEnums::PROTEIN_VIEW->value,
    ]);
    $protein = createProtein([
        'uniprot_id' => 'Q9Y6K9',
    ]);

    $this->actingAs($user);

    Livewire::test(ListProteins::class)
        ->assertOk()
        ->assertSee((string) $protein->id)
        ->assertSee($protein->uniprot_id);
});

test('user with protein view and edit permission can load the create protein page', function () {
    $user = createProteinAdmin([
        PermissionEnums::PROTEIN_VIEW->value,
        PermissionEnums::PROTEIN_EDIT->value,
    ]);
    createProteinResourceCategory();

    $this->actingAs($user);

    Livewire::test(CreateProtein::class)
        ->assertOk()
        ->assertFormFieldExists('uniprot_id')
        ->assertFormFieldExists('categories');
});

test('user without protein edit permission cannot load the create protein page', function () {
    $user = createProteinAdmin([
        PermissionEnums::PROTEIN_VIEW->value,
    ]);
    createProteinResourceCategory();

    $this->actingAs($user);

    Livewire::test(CreateProtein::class)
        ->assertForbidden();
});

test('user with protein edit permission can create a protein', function () {
    $user = createProteinAdmin([
        PermissionEnums::PROTEIN_VIEW->value,
        PermissionEnums::PROTEIN_EDIT->value,
    ]);
    $category = createProteinResourceCategory();

    $this->actingAs($user);

    Livewire::test(CreateProtein::class)
        ->fillForm([
            'uniprot_id' => 'O15440',
            'categories' => [$category->id],
        ])
        ->call('create')
        ->assertNotified()
        ->assertRedirect();

    $protein = Protein::query()
        ->where('uniprot_id', 'O15440')
        ->first();

    expect($protein)->not->toBeNull()
        ->and($protein->categories()->whereKey($category->id)->exists())->toBeTrue();
});

test('user with protein view and edit permission can load the edit protein page', function () {
    $user = createProteinAdmin([
        PermissionEnums::PROTEIN_VIEW->value,
        PermissionEnums::PROTEIN_EDIT->value,
    ]);
    $protein = createProtein();

    $this->actingAs($user);

    Livewire::test(EditProtein::class, [
        'record' => $protein->getKey(),
    ])
        ->assertOk()
        ->assertFormSet([
            'uniprot_id' => $protein->uniprot_id,
        ]);
});

test('user without protein edit permission cannot load the edit protein page', function () {
    $user = createProteinAdmin([
        PermissionEnums::PROTEIN_VIEW->value,
    ]);
    $protein = createProtein();

    $this->actingAs($user);

    Livewire::test(EditProtein::class, [
        'record' => $protein->getKey(),
    ])
        ->assertForbidden();
});

test('user with protein edit permission can update a protein', function () {
    $user = createProteinAdmin([
        PermissionEnums::PROTEIN_VIEW->value,
        PermissionEnums::PROTEIN_EDIT->value,
    ]);
    $protein = createProtein();
    $category = createProteinResourceCategory([
        'title' => 'Carrier proteins',
    ]);

    $this->actingAs($user);

    Livewire::test(EditProtein::class, [
        'record' => $protein->getKey(),
    ])
        ->fillForm([
            'uniprot_id' => 'Q8N1B4',
            'categories' => [$category->id],
        ])
        ->call('save')
        ->assertNotified();

    $updatedProtein = $protein->fresh();

    expect($updatedProtein)
        ->uniprot_id->toBe('Q8N1B4');

    expect($updatedProtein->categories()->whereKey($category->id)->exists())->toBeTrue();
});

test('protein policy allows global view create update and delete permissions', function () {
    $user = createProteinAdmin([
        PermissionEnums::PROTEIN_VIEW->value,
        PermissionEnums::PROTEIN_EDIT->value,
        PermissionEnums::PROTEIN_DELETE->value,
    ]);
    $protein = createProtein();

    expect($user->can('view', $protein))->toBeTrue()
        ->and($user->can('create', Protein::class))->toBeTrue()
        ->and($user->can('update', $protein))->toBeTrue()
        ->and($user->can('delete', $protein))->toBeTrue()
        ->and($user->can('restore', $protein))->toBeTrue();
});

test('protein resource exposes index create and edit pages', function () {
    expect(ProteinResource::getPages())
        ->toHaveKeys(['index', 'create', 'edit']);
});
