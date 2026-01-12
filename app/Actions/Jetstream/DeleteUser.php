<?php

namespace App\Actions\Jetstream;

use App\Models\User;
use Laravel\Jetstream\Contracts\DeletesUsers;

class DeleteUser implements DeletesUsers
{
    /**
     * Delete the given user.
     */
    public function delete(User $user): void
    {
        if (($user->role ?? 'user') === 'admin') {
            abort(403, 'Admins cannot be deleted.');
        }

        $user->tokens->each->delete();
        $user->forceFill([
            'role' => $user->role ?? 'user',
            'status' => 'deleted',
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
        ])->save();
    }
}
