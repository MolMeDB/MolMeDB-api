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
            PermissionEnums::PROTEIN_VIEW,
            PermissionEnums::PROTEIN_VIEW_OWN,
            PermissionEnums::PROTEIN_EDIT,
            PermissionEnums::PROTEIN_EDIT_OWN,
            PermissionEnums::PROTEIN_DELETE,
            PermissionEnums::PROTEIN_DELETE_OWN,
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
            PermissionEnums::PROTEIN_VIEW->value,
            PermissionEnums::PROTEIN_VIEW_OWN->value,
            PermissionEnums::PROTEIN_EDIT->value,
            PermissionEnums::PROTEIN_EDIT_OWN->value,
            PermissionEnums::PROTEIN_DELETE->value,
            PermissionEnums::PROTEIN_DELETE_OWN->value,
        ];

        Permission::query()
            ->whereIn('name', $permissionNames)
            ->where('guard_name', 'web')
            ->delete();
    }
};
