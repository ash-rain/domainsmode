<?php

namespace App\Policies;

use App\Models\User;

class ContentPolicy
{
    /**
     * Determine whether the user can create content.
     * Any authenticated user may create content.
     */
    public function create(User $user): bool
    {
        return true;
    }
}
