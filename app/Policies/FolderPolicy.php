<?php

namespace App\Policies;

use App\Models\Folder;
use App\Models\User;

class FolderPolicy
{
    /**
     * A user may modify or delete a folder if they created it, they are a
     * global admin, or they are a member of the folder's space.
     */
    public function update(User $user, Folder $folder): bool
    {
        return $this->canManage($user, $folder);
    }

    public function delete(User $user, Folder $folder): bool
    {
        return $this->canManage($user, $folder);
    }

    private function canManage(User $user, Folder $folder): bool
    {
        if ($user->isAdmin() || $folder->created_by === $user->id) {
            return true;
        }

        return $folder->space?->hasMember($user->id) === true;
    }
}
