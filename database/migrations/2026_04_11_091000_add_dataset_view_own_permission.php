<?php

use App\Enums\PermissionEnums;
use App\Enums\RoleEnums;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $tableNames = config('permission.table_names');

        DB::table($tableNames['permissions'])->updateOrInsert(
            [
                'name' => PermissionEnums::DATASET_VIEW_OWN->value,
                'guard_name' => 'web',
            ],
            [
                'description' => PermissionEnums::DATASET_VIEW_OWN->description(),
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );

        $permission = DB::table($tableNames['permissions'])
            ->where('name', PermissionEnums::DATASET_VIEW_OWN->value)
            ->where('guard_name', 'web')
            ->first();

        $adminRole = DB::table($tableNames['roles'])
            ->where('name', RoleEnums::ADMIN->value)
            ->where('guard_name', 'web')
            ->first();

        if ($permission && $adminRole) {
            DB::table($tableNames['role_has_permissions'])->updateOrInsert(
                [
                    'permission_id' => $permission->id,
                    'role_id' => $adminRole->id,
                ],
                [],
            );
        }

        app('cache')
            ->store(config('permission.cache.store') !== 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));
    }

    public function down(): void
    {
        $tableNames = config('permission.table_names');

        $permission = DB::table($tableNames['permissions'])
            ->where('name', PermissionEnums::DATASET_VIEW_OWN->value)
            ->where('guard_name', 'web')
            ->first();

        if ($permission) {
            DB::table($tableNames['role_has_permissions'])
                ->where('permission_id', $permission->id)
                ->delete();

            DB::table($tableNames['model_has_permissions'])
                ->where('permission_id', $permission->id)
                ->delete();

            DB::table($tableNames['permissions'])
                ->where('id', $permission->id)
                ->delete();
        }

        app('cache')
            ->store(config('permission.cache.store') !== 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));
    }
};
