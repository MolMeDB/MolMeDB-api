<?php

namespace App\Policies;

use App\Enums\PermissionEnums;
use App\Models\SshCredential;
use App\Models\User;

class SshCredentialPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo(PermissionEnums::SSH_CREDENTIALS_MANAGE);
    }

    public function view(User $user, SshCredential $sshCredential): bool
    {
        return $user->hasPermissionTo(PermissionEnums::SSH_CREDENTIALS_MANAGE);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo(PermissionEnums::SSH_CREDENTIALS_MANAGE);
    }

    public function update(User $user, SshCredential $sshCredential): bool
    {
        return $user->hasPermissionTo(PermissionEnums::SSH_CREDENTIALS_MANAGE);
    }

    public function delete(User $user, SshCredential $sshCredential): bool
    {
        return $user->hasPermissionTo(PermissionEnums::SSH_CREDENTIALS_MANAGE);
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasPermissionTo(PermissionEnums::SSH_CREDENTIALS_MANAGE);
    }

    public function restore(User $user, SshCredential $sshCredential): bool
    {
        return $user->hasPermissionTo(PermissionEnums::SSH_CREDENTIALS_MANAGE);
    }

    public function restoreAny(User $user): bool
    {
        return $user->hasPermissionTo(PermissionEnums::SSH_CREDENTIALS_MANAGE);
    }

    public function forceDelete(User $user, SshCredential $sshCredential): bool
    {
        return $user->hasPermissionTo(PermissionEnums::SSH_CREDENTIALS_MANAGE);
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->hasPermissionTo(PermissionEnums::SSH_CREDENTIALS_MANAGE);
    }
}
