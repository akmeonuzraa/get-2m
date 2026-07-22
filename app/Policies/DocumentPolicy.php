<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    /**
     * A user may access a document if they uploaded it, they are a global
     * admin, or the document belongs to a space they are a member of.
     */
    public function view(User $user, Document $document): bool
    {
        return $this->canAccess($user, $document);
    }

    /**
     * Trashing / restoring a document is treated as an update.
     */
    public function update(User $user, Document $document): bool
    {
        return $this->canAccess($user, $document);
    }

    /**
     * Permanently deleting a document is restricted to the uploader and
     * global admins.
     */
    public function delete(User $user, Document $document): bool
    {
        return $user->isAdmin() || $document->uploaded_by === $user->id;
    }

    private function canAccess(User $user, Document $document): bool
    {
        if ($user->isAdmin() || $document->uploaded_by === $user->id) {
            return true;
        }

        return $document->space_id !== null
            && $document->space?->hasMember($user->id) === true;
    }
}
