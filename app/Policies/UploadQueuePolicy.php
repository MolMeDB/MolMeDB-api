<?php

namespace App\Policies;

use App\Enums\PermissionEnums;
use App\Models\UploadQueue;
use App\Models\User;

class UploadQueuePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAdminRole()
            || $user->hasPermissionTo(PermissionEnums::UPLOAD_QUEUE_MANAGE_OWN)
            || $user->hasPermissionTo(PermissionEnums::UPLOAD_QUEUE_MANAGE_ALL);
    }

    public function view(User $user, UploadQueue $uploadQueue): bool
    {
        return $this->canManage($user, $uploadQueue);
    }

    public function configure(User $user, UploadQueue $uploadQueue): bool
    {
        return $this->canManage($user, $uploadQueue);
    }

    public function enqueue(User $user, UploadQueue $uploadQueue): bool
    {
        return $this->canManage($user, $uploadQueue);
    }

    public function reupload(User $user, UploadQueue $uploadQueue): bool
    {
        return $this->canManage($user, $uploadQueue);
    }

    public function cancel(User $user, UploadQueue $uploadQueue): bool
    {
        return $uploadQueue->canDeleteUploadedFile($user);
    }

    public function revert(User $user, UploadQueue $uploadQueue): bool
    {
        return $this->canManage($user, $uploadQueue);
    }

    public function delete(User $user, UploadQueue $uploadQueue): bool
    {
        return $uploadQueue->canDeleteUploadedFile($user);
    }

    private function canManage(User $user, UploadQueue $uploadQueue): bool
    {
        return $user->hasAdminRole()
            || $uploadQueue->user_id === $user->id
            || $user->hasPermissionTo(PermissionEnums::UPLOAD_QUEUE_MANAGE_ALL);
    }
}
