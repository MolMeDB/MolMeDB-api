<?php

namespace App\Policies;

use App\Enums\PermissionEnums;
use App\Models\Structure;
use App\Models\User;

class StructurePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo(PermissionEnums::STRUCTURE_VIEW)
            || $user->hasPermissionTo(PermissionEnums::STRUCTURE_VIEW_OWN);
    }

    public function view(User $user, Structure $structure): bool
    {
        return $user->hasPermissionTo(PermissionEnums::STRUCTURE_VIEW)
            || (
                $user->hasPermissionTo(PermissionEnums::STRUCTURE_VIEW_OWN)
                && $structure->user_id === $user->id
            );
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo(PermissionEnums::STRUCTURE_EDIT)
            || $user->hasPermissionTo(PermissionEnums::STRUCTURE_EDIT_OWN);
    }

    public function update(User $user, Structure $structure): bool
    {
        return $user->hasPermissionTo(PermissionEnums::STRUCTURE_EDIT)
            || (
                $user->hasPermissionTo(PermissionEnums::STRUCTURE_EDIT_OWN)
                && $structure->user_id === $user->id
            );
    }

    public function delete(User $user, Structure $structure): bool
    {
        return $user->hasPermissionTo(PermissionEnums::STRUCTURE_DELETE)
            || (
                $user->hasPermissionTo(PermissionEnums::STRUCTURE_DELETE_OWN)
                && $structure->user_id === $user->id
            );
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasPermissionTo(PermissionEnums::STRUCTURE_DELETE);
    }

    public function restore(User $user, Structure $structure): bool
    {
        return $this->delete($user, $structure);
    }

    public function restoreAny(User $user): bool
    {
        return $this->deleteAny($user);
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }

    public function forceDelete(User $user, Structure $structure): bool
    {
        return false;
    }
}
