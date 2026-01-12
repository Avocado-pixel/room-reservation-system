<?php

namespace App\Policies;

use App\Models\Feedback;
use App\Models\User;

class FeedbackPolicy
{
    public function delete(User $user, Feedback $feedback): bool
    {
        return $user->isAdmin() || (int) $feedback->user_id === (int) $user->id;
    }

    public function updateStatus(User $user): bool
    {
        return $user->isAdmin();
    }
}
