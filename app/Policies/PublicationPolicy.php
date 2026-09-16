<?php

namespace App\Policies;

use App\Enums\PermissionEnums;
use App\Models\Publication;
use App\Models\User;

class PublicationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo(PermissionEnums::PUBLICATION_VIEW)
            || $user->hasPermissionTo(PermissionEnums::PUBLICATION_VIEW_OWN);
    }

    public function view(User $user, Publication $publication): bool
    {
        return $user->hasPermissionTo(PermissionEnums::PUBLICATION_VIEW)
            || (
                $user->hasPermissionTo(PermissionEnums::PUBLICATION_VIEW_OWN)
                && $publication->user_id === $user->id
            );
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo(PermissionEnums::PUBLICATION_EDIT)
            || $user->hasPermissionTo(PermissionEnums::PUBLICATION_EDIT_OWN);
    }

    public function update(User $user, Publication $publication): bool
    {
        return $user->hasPermissionTo(PermissionEnums::PUBLICATION_EDIT)
            || (
                $user->hasPermissionTo(PermissionEnums::PUBLICATION_EDIT_OWN)
                && $publication->user_id === $user->id
            );
    }

    public function delete(User $user, Publication $publication): bool
    {
        return $user->hasPermissionTo(PermissionEnums::PUBLICATION_DELETE)
            || (
                $user->hasPermissionTo(PermissionEnums::PUBLICATION_DELETE_OWN)
                && $publication->user_id === $user->id
            );
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasPermissionTo(PermissionEnums::PUBLICATION_DELETE);
    }

    public function restore(User $user, Publication $publication): bool
    {
        return $this->delete($user, $publication);
    }

    public function restoreAny(User $user): bool
    {
        return $this->deleteAny($user);
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }

    public function forceDelete(User $user, Publication $publication): bool
    {
        return false;
    }
}
