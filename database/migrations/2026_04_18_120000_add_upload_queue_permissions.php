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
            PermissionEnums::UPLOAD_QUEUE_MANAGE_OWN,
            PermissionEnums::UPLOAD_QUEUE_MANAGE_ALL,
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
            PermissionEnums::UPLOAD_QUEUE_MANAGE_OWN->value,
            PermissionEnums::UPLOAD_QUEUE_MANAGE_ALL->value,
        ];

        Permission::query()
            ->whereIn('name', $permissionNames)
            ->where('guard_name', 'web')
            ->delete();
    }
};
