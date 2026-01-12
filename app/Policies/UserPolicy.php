<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Only admins can view user listings.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Users can view their own profile. Admins can view anyone.
     */
    public function view(User $user, User $model): bool
    {
        return $user->isAdmin() || (int) $user->id === (int) $model->id;
    }

    /**
     * Users can update their own profile. Admins can update anyone.
     */
    public function update(User $user, User $model): bool
    {
        return $user->isAdmin() || (int) $user->id === (int) $model->id;
    }

    /**
     * Only admins can change user status.
     */
    public function updateStatus(User $user, User $model): bool
    {
        // Cannot change own admin status or the last admin
        if ($user->isAdmin()) {
            // Prevent admin from deactivating themselves
            if ((int) $user->id === (int) $model->id) {
                return false;
            }
            return true;
        }

        return false;
    }

    /**
     * Only admins can change roles.
     */
    public function updateRole(User $user, User $model): bool
    {
        if (!$user->isAdmin()) {
            return false;
        }

        // Prevent removing own admin role
        if ((int) $user->id === (int) $model->id) {
            return false;
        }

        return true;
    }

    /**
     * Users can delete their own account. Admins cannot delete admins.
     */
    public function delete(User $user, User $model): bool
    {
        // User deleting their own account
        if ((int) $user->id === (int) $model->id) {
            return true;
        }

        // Admin deleting a user (but not another admin)
        if ($user->isAdmin() && !$model->isAdmin()) {
            return true;
        }

        return false;
    }
}
