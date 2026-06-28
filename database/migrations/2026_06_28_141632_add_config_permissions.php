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
        $permissions = collect([
            PermissionEnums::CONFIG_VIEW,
            PermissionEnums::CONFIG_EDIT,
        ])->map(fn (PermissionEnums $permission): Permission => Permission::query()->firstOrCreate([
            'name' => $permission->value,
            'guard_name' => 'web',
        ], [
            'description' => $permission->description(),
        ]));

        $adminRole = Role::query()
            ->where('name', RoleEnums::ADMIN->value)
            ->where('guard_name', 'web')
            ->first();

        $adminRole?->givePermissionTo($permissions);
    }

    public function down(): void
    {
        Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('name', [
                PermissionEnums::CONFIG_VIEW->value,
                PermissionEnums::CONFIG_EDIT->value,
            ])
            ->delete();
    }
};
