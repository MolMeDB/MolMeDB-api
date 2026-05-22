<?php

use App\Enums\PermissionEnums;
use App\Enums\RoleEnums;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            PermissionEnums::PUBLICATION_VIEW,
            PermissionEnums::PUBLICATION_VIEW_OWN,
            PermissionEnums::PUBLICATION_EDIT,
            PermissionEnums::PUBLICATION_EDIT_OWN,
            PermissionEnums::PUBLICATION_DELETE,
            PermissionEnums::PUBLICATION_DELETE_OWN,
        ];

        foreach ($permissions as $permissionEnum) {
            Permission::query()->firstOrCreate([
                'name' => $permissionEnum->value,
                'guard_name' => 'web',
            ], [
                'description' => $permissionEnum->description(),
            ]);
        }

        $adminRole = Role::query()
            ->where('name', RoleEnums::ADMIN->value)
            ->where('guard_name', 'web')
            ->first();

        if ($adminRole) {
            $adminRole->givePermissionTo(
                Permission::query()
                    ->whereIn('name', array_map(fn (PermissionEnums $permissionEnum): string => $permissionEnum->value, $permissions))
                    ->get(),
            );
        }
    }

    public function down(): void
    {
        $permissionNames = [
            PermissionEnums::PUBLICATION_VIEW->value,
            PermissionEnums::PUBLICATION_VIEW_OWN->value,
            PermissionEnums::PUBLICATION_EDIT->value,
            PermissionEnums::PUBLICATION_EDIT_OWN->value,
            PermissionEnums::PUBLICATION_DELETE->value,
            PermissionEnums::PUBLICATION_DELETE_OWN->value,
        ];

        Permission::query()
            ->whereIn('name', $permissionNames)
            ->where('guard_name', 'web')
            ->delete();
    }
};
