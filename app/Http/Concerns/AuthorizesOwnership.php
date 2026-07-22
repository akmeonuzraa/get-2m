<?php

namespace App\Http\Concerns;

use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Database\Eloquent\Model;

trait AuthorizesOwnership
{
    /**
     * Abort with a 403 response unless the given user owns the model.
     *
     * Ownership is determined by the model's `user_id` column. When
     * $allowAdmin is true, administrators bypass the ownership check.
     */
    protected function denyUnlessOwner(Model $model, ?User $user, bool $allowAdmin = false): void
    {
        $isOwner = $user !== null && $model->getAttribute('user_id') === $user->id;

        if ($isOwner || ($allowAdmin && $user !== null && $user->isAdmin())) {
            return;
        }

        abort(ApiResponse::forbidden());
    }
}
