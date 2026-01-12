<?php

namespace App\Http\Responses;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;

class RegisterResponse implements RegisterResponseContract
{
    public function toResponse($request): RedirectResponse
    {
        // After registration, take the user straight to the validation page.
        // Keep the user logged out: verify first (link or code), then log in.
        $email = $request instanceof Request ? (string) optional($request->user())->email : '';

        if ($request instanceof Request && $request->user()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return redirect()->route('validate-account.show')
            ->with('status', 'Account created. Check your email to verify your account (link or code).');
    }
}
