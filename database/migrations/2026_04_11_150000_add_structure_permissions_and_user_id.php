<?php

use App\Enums\PermissionEnums;
use App\Enums\RoleEnums;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('structures', function (Blueprint $table): void {
            if (! Schema::hasColumn('structures', 'user_id')) {
                $table->foreignId('user_id')
                    ->nullable()
                    ->after('parent_id')
                    ->constrained('users')
                    ->restrictOnDelete();
            }
        });

        $permissions = [
            PermissionEnums::STRUCTURE_VIEW,
            PermissionEnums::STRUCTURE_VIEW_OWN,
            PermissionEnums::STRUCTURE_EDIT,
            PermissionEnums::STRUCTURE_EDIT_OWN,
            PermissionEnums::STRUCTURE_DELETE,
            PermissionEnums::STRUCTURE_DELETE_OWN,
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
            PermissionEnums::STRUCTURE_VIEW->value,
            PermissionEnums::STRUCTURE_VIEW_OWN->value,
            PermissionEnums::STRUCTURE_EDIT->value,
            PermissionEnums::STRUCTURE_EDIT_OWN->value,
            PermissionEnums::STRUCTURE_DELETE->value,
            PermissionEnums::STRUCTURE_DELETE_OWN->value,
        ];

        Permission::query()
            ->whereIn('name', $permissionNames)
            ->where('guard_name', 'web')
            ->delete();

        Schema::table('structures', function (Blueprint $table): void {
            if (Schema::hasColumn('structures', 'user_id')) {
                $table->dropConstrainedForeignId('user_id');
            }
        });
    }
};
