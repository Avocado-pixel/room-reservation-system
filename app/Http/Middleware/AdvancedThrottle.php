<?php

namespace App\Http\Middleware;

use App\Services\Security\IpBlacklistService;
use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdvancedThrottle
{
    public function __construct(
        private readonly RateLimiter $limiter,
        private readonly IpBlacklistService $blacklistService,
    ) {
    }

    /**
     * Handle rate limiting with automatic IP blacklisting on repeated violations.
     *
     * Usage: throttle.advanced:60,1  (60 requests per minute)
     */
    public function handle(Request $request, Closure $next, int $maxAttempts = 60, int $decayMinutes = 1): Response
    {
        $ip = $request->ip();
        $userId = $request->user()?->id;
        $key = $this->resolveRequestSignature($request);

        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            $this->blacklistService->recordAbuseAttempt($ip, $userId);

            $retryAfter = $this->limiter->availableIn($key);

            return response()->json([
                'error' => 'rate_limit_exceeded',
                'message' => 'Too many requests. Please slow down.',
                'retry_after' => $retryAfter,
            ], Response::HTTP_TOO_MANY_REQUESTS)
                ->header('Retry-After', $retryAfter)
                ->header('X-RateLimit-Limit', $maxAttempts)
                ->header('X-RateLimit-Remaining', 0);
        }

        $this->limiter->hit($key, $decayMinutes * 60);

        $response = $next($request);

        return $this->addRateLimitHeaders($response, $maxAttempts, $this->calculateRemainingAttempts($key, $maxAttempts));
    }

    protected function resolveRequestSignature(Request $request): string
    {
        $userId = $request->user()?->id;

        if ($userId) {
            return 'throttle:user:' . $userId . ':' . $request->path();
        }

        return 'throttle:ip:' . $request->ip() . ':' . $request->path();
    }

    protected function calculateRemainingAttempts(string $key, int $maxAttempts): int
    {
        return max(0, $maxAttempts - $this->limiter->attempts($key));
    }

    protected function addRateLimitHeaders(Response $response, int $maxAttempts, int $remainingAttempts): Response
    {
        $response->headers->set('X-RateLimit-Limit', (string) $maxAttempts, false);
        $response->headers->set('X-RateLimit-Remaining', (string) $remainingAttempts, false);

        return $response;
    }
}
