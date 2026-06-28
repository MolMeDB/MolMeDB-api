<?php

namespace App\Policies;

use App\Enums\PermissionEnums;
use App\Models\Config;
use App\Models\User;

class ConfigPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $this->canView($user);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Config $config): bool
    {
        return $this->canView($user);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->canEdit($user);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Config $config): bool
    {
        return $this->canEdit($user);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Config $config): bool
    {
        return $this->canEdit($user);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Config $config): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Config $config): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return $this->canEdit($user);
    }

    private function canView(User $user): bool
    {
        return $user->hasPermissionTo(PermissionEnums::CONFIG_VIEW)
            || $this->canEdit($user);
    }

    private function canEdit(User $user): bool
    {
        return $user->hasPermissionTo(PermissionEnums::CONFIG_EDIT);
    }
}
