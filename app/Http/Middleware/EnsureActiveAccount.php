<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to ensure the authenticated user has an active account.
 *
 * Non-admin users with inactive, suspended, or pending accounts will receive
 * a 403 Forbidden response. Administrators bypass this check to maintain
 * system access for account management purposes.
 *
 * @category Security
 * @package  App\Http\Middleware
 */
class EnsureActiveAccount
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(401);
        }

        $status = $user->status ?? 'pending';

        // Admins can keep access even if the account is in a non-standard state.
        if (($user->role ?? null) === 'admin') {
            return $next($request);
        }

        if ($status !== 'active') {
            abort(403, 'Account unavailable. Contact an administrator.');
        }

        return $next($request);
    }
}
