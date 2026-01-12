<?php

namespace App\Http\Middleware;

use App\Services\Security\IpBlacklistService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware: IP Blacklist Check (Anti-DDoS Layer 1).
 *
 * This is the first line of defense in the security middleware chain.
 * It checks if the requesting IP is in the blacklist and blocks access
 * with a 429 Too Many Requests response if so.
 *
 * This middleware should be registered early in the global middleware
 * stack to minimize server load from blocked IPs.
 *
 * Response for blocked IPs:
 * - HTTP 429 Too Many Requests
 * - Retry-After header with remaining ban time
 * - JSON body with error details
 *
 * @see \App\Services\Security\IpBlacklistService For blacklist management
 * @see \App\Http\Middleware\AdvancedThrottle For rate limiting that triggers blacklisting
 */
class CheckIpBlacklist
{
    /**
     * Create a new middleware instance.
     *
     * @param IpBlacklistService $blacklistService Service for IP blacklist checks
     */
    public function __construct(
        private readonly IpBlacklistService $blacklistService,
    ) {
    }

    /**
     * Handle an incoming request.
     *
     * Checks if the client IP is blacklisted. If so, returns a 429 response
     * with retry information. Otherwise, passes the request to the next handler.
     *
     * @param Request $request The incoming HTTP request
     * @param Closure $next The next middleware handler
     * @return Response The HTTP response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();

        if ($this->blacklistService->isBlacklisted($ip)) {
            $remainingMinutes = $this->blacklistService->getRemainingBanMinutes($ip);
            $retryAfterSeconds = max(1, $remainingMinutes * 60);

            // Return JSON for API/AJAX requests, HTML page for browser requests
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'error' => 'access_denied',
                    'message' => 'Your IP has been temporarily blocked due to suspicious activity.',
                    'retry_after_minutes' => $remainingMinutes,
                ], Response::HTTP_TOO_MANY_REQUESTS)
                    ->header('Retry-After', $retryAfterSeconds);
            }

            // Return a nice HTML page for browser requests
            return response()
                ->view('errors.blocked', [
                    'remainingMinutes' => $remainingMinutes,
                ], Response::HTTP_TOO_MANY_REQUESTS)
                ->header('Retry-After', $retryAfterSeconds)
                ->header('Cache-Control', 'no-store, no-cache, must-revalidate');
        }

        return $next($request);
    }
}
