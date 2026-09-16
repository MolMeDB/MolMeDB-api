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
            'name' => PermissionEnums::PREDICTION_DATASET_MANAGE_ALL->value,
            'guard_name' => 'web',
        ], [
            'description' => PermissionEnums::PREDICTION_DATASET_MANAGE_ALL->description(),
        ]);

        $adminRole = Role::query()
            ->where('name', RoleEnums::ADMIN->value)
            ->where('guard_name', 'web')
            ->first();

        if ($adminRole) {
            $adminRole->givePermissionTo($permission);
        }
    }

    public function down(): void
    {
        Permission::query()
            ->where('name', PermissionEnums::PREDICTION_DATASET_MANAGE_ALL->value)
            ->where('guard_name', 'web')
            ->delete();
    }
};
