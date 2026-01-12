<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Handles email verification via signed URLs.
 *
 * Verifies user email addresses through secure, signed links
 * sent during registration. Also activates pending accounts.
 *
 * @category Public
 * @package  App\Http\Controllers\Public
 */
class EmailVerificationController extends Controller
{
    /**
     * Verify user email via signed URL.
     */
    public function verify(Request $request, int $id, string $hash): RedirectResponse
    {
        $user = User::query()->findOrFail($id);

        // Extra safety: ensure the hash matches the user's email.
        if (!hash_equals(sha1($user->getEmailForVerification()), (string) $hash)) {
            abort(403);
        }

        if (is_null($user->email_verified_at)) {
            $user->forceFill([
                'email_verified_at' => now(),
            ])->save();
        }

        if (($user->status ?? 'pending') !== 'active') {
            $user->forceFill([
                'status' => 'active',
                'email_validation_token' => null,
                'email_validation_expires_at' => null,
            ])->save();
        }

        return redirect()->route('login')->with('status', 'Your account was verified. You can now log in.');
    }
}
