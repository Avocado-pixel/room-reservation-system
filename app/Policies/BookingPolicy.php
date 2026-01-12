<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    public function view(User $user, Booking $booking): bool
    {
        return $user->isAdmin() || (int) $booking->user_id === (int) $user->id;
    }

    public function update(User $user, Booking $booking): bool
    {
        return $user->isAdmin() || (int) $booking->user_id === (int) $user->id;
    }

    public function delete(User $user, Booking $booking): bool
    {
        return $user->isAdmin() || (int) $booking->user_id === (int) $user->id;
    }
}
