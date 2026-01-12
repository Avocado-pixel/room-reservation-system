<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\AccountVerificationOptions;
use App\Support\SawKeys;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Throwable;

/**
 * Handles account validation via 6-digit verification codes.
 *
 * Provides a code-based verification flow for users who prefer
 * not to click email links or are on mobile devices.
 *
 * @category Public
 * @package  App\Http\Controllers\Public
 */
class AccountValidationController extends Controller
{
    /**
     * Display the account validation form.
     */
    public function show(Request $request): View
    {
        return view('public.validate-account', [
            'email' => (string) $request->query('email', ''),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'regex:/^\d{6}$/'],
        ]);

        $user = User::query()->where('email', $validated['email'])->first();

        if (!$user) {
            return back()->withErrors(['email' => 'User not found.'])->withInput();
        }

        if (($user->status ?? 'pending') === 'active') {
            return redirect()->route('login')->with('status', 'Your account is already active. You can log in.');
        }

        if (empty($user->email_validation_token) || empty($user->email_validation_expires_at)) {
            return back()->withErrors(['code' => 'There is no active validation request. Request a new code.'])->withInput();
        }

        $expires = Carbon::parse($user->email_validation_expires_at);
        if ($expires->isPast()) {
            return back()->withErrors(['code' => 'Code expired. Request a new code.'])->withInput();
        }

        $expected = hash_hmac('sha256', (string) $validated['code'], SawKeys::hmacKey());
        if (!hash_equals((string) $expected, (string) $user->email_validation_token)) {
            return back()->withErrors(['code' => 'Invalid code.'])->withInput();
        }

        $user->forceFill([
            'status' => 'active',
            'email_validation_token' => null,
            'email_validation_expires_at' => null,
            'email_verified_at' => now(),
        ])->save();

        return redirect()->route('login')->with('status', 'Your account was verified. You can now log in.');
    }

    public function resend(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::query()->where('email', $validated['email'])->first();

        if (!$user) {
            return back()->withErrors(['email' => 'User not found.'])->withInput();
        }

        if (($user->status ?? 'pending') === 'active') {
            return redirect()->route('login')->with('status', 'Your account is already active. You can log in.');
        }

        $code = (string) random_int(100000, 999999);
        $token = hash_hmac('sha256', $code, SawKeys::hmacKey());

        $user->forceFill([
            'email_validation_token' => $token,
            'email_validation_expires_at' => now()->addMinutes(30),
        ])->save();

        try {
            $user->notify(new AccountVerificationOptions($code));
        } catch (Throwable $e) {
            return back()->withErrors(['email' => 'We could not send the email right now. Please try again later.'])->withInput();
        }

        return back()->with('status', 'A new code was sent. Check your email.');
    }
}
