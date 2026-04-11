<?php

namespace App\Policies;

use App\Enums\PermissionEnums;
use App\Models\Protein;
use App\Models\User;

class ProteinPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo(PermissionEnums::PROTEIN_VIEW)
            || $user->hasPermissionTo(PermissionEnums::PROTEIN_VIEW_OWN);
    }

    public function view(User $user, Protein $protein): bool
    {
        return $user->hasPermissionTo(PermissionEnums::PROTEIN_VIEW)
            || (
                $user->hasPermissionTo(PermissionEnums::PROTEIN_VIEW_OWN)
                && $protein->user_id === $user->id
            );
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo(PermissionEnums::PROTEIN_EDIT)
            || $user->hasPermissionTo(PermissionEnums::PROTEIN_EDIT_OWN);
    }

    public function update(User $user, Protein $protein): bool
    {
        return $user->hasPermissionTo(PermissionEnums::PROTEIN_EDIT)
            || (
                $user->hasPermissionTo(PermissionEnums::PROTEIN_EDIT_OWN)
                && $protein->user_id === $user->id
            );
    }

    public function delete(User $user, Protein $protein): bool
    {
        return $user->hasPermissionTo(PermissionEnums::PROTEIN_DELETE)
            || (
                $user->hasPermissionTo(PermissionEnums::PROTEIN_DELETE_OWN)
                && $protein->user_id === $user->id
            );
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasPermissionTo(PermissionEnums::PROTEIN_DELETE);
    }

    public function restore(User $user, Protein $protein): bool
    {
        return $this->delete($user, $protein);
    }

    public function restoreAny(User $user): bool
    {
        return $this->deleteAny($user);
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }

    public function forceDelete(User $user, Protein $protein): bool
    {
        return false;
    }
}
