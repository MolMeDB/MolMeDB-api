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
        $permission = Permission::query()->firstOrCreate([
            'name' => PermissionEnums::SYSTEM_MONITOR->value,
            'guard_name' => 'web',
        ], [
            'description' => PermissionEnums::SYSTEM_MONITOR->description(),
        ]);

        $adminRole = Role::query()
            ->where('name', RoleEnums::ADMIN->value)
            ->where('guard_name', 'web')
            ->first();

        $adminRole?->givePermissionTo($permission);
    }

    public function down(): void
    {
        Permission::query()
            ->where('guard_name', 'web')
            ->where('name', PermissionEnums::SYSTEM_MONITOR->value)
            ->delete();
    }
};
