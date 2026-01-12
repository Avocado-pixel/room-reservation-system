<?php

namespace App\Policies;

use App\Models\Room;
use App\Models\User;

class RoomPolicy
{
    /**
     * Anyone can view active rooms.
     */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /**
     * Anyone can view an active room. Deleted rooms require admin.
     */
    public function view(?User $user, Room $room): bool
    {
        if ($room->record_status === 'deleted') {
            return $user?->isAdmin() ?? false;
        }

        return true;
    }

    /**
     * Only admins can create rooms.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Only admins can update rooms.
     */
    public function update(User $user, Room $room): bool
    {
        return $user->isAdmin() && $room->record_status !== 'deleted';
    }

    /**
     * Only admins can delete rooms.
     */
    public function delete(User $user, Room $room): bool
    {
        return $user->isAdmin() && $room->record_status !== 'deleted';
    }

    /**
     * Only admins can manage room policies (cancellation, etc.)
     */
    public function managePolicy(User $user, Room $room): bool
    {
        return $user->isAdmin();
    }
}
