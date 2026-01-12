<?php

namespace App\Policies;

use App\Models\Reserva;
use App\Models\User;

class ReservaPolicy
{
    public function view(User $user, Reserva $reserva): bool
    {
        return ($user->perfil ?? null) === 'admin' || (int) $reserva->user_id === (int) $user->id;
    }

    public function update(User $user, Reserva $reserva): bool
    {
        return ($user->perfil ?? null) === 'admin' || (int) $reserva->user_id === (int) $user->id;
    }

    public function delete(User $user, Reserva $reserva): bool
    {
        return ($user->perfil ?? null) === 'admin' || (int) $reserva->user_id === (int) $user->id;
    }
}
