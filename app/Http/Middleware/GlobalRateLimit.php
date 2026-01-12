<?php

namespace App\Http\Middleware;

use App\Services\Security\IpBlacklistService;
use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Global rate limiting middleware applied to all web requests.
 * 
 * Limits requests per IP/user and provides visual feedback via headers.
 * The frontend can read these headers to warn users before they hit the limit.
 */
class GlobalRateLimit
{
    /** @var int Maximum requests per decay period */
    private const MAX_REQUESTS = 60;

    /** @var int Decay period in minutes */
    private const DECAY_MINUTES = 1;

    /** @var int Threshold percentage to start warning users */
    private const WARNING_THRESHOLD = 0.7; // 70% = start warning

    public function __construct(
        private readonly RateLimiter $limiter,
        private readonly IpBlacklistService $blacklistService,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        // Skip rate limiting for assets and static files
        if ($this->shouldSkip($request)) {
            return $next($request);
        }

        $key = $this->resolveKey($request);
        $maxAttempts = self::MAX_REQUESTS;
        $decaySeconds = self::DECAY_MINUTES * 60;

        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            $ip = $request->ip();
            $this->blacklistService->recordAbuseAttempt($ip, $request->user()?->id);

            $retryAfter = $this->limiter->availableIn($key);
            $retryMinutes = (int) ceil($retryAfter / 60);

            // Return JSON for API/AJAX requests
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'error' => 'rate_limit_exceeded',
                    'message' => __('Too many requests. Please wait before trying again.'),
                    'retry_after' => $retryAfter,
                ], Response::HTTP_TOO_MANY_REQUESTS)
                    ->header('Retry-After', $retryAfter)
                    ->header('X-RateLimit-Limit', $maxAttempts)
                    ->header('X-RateLimit-Remaining', 0)
                    ->header('X-RateLimit-Warning', 'true');
            }

            // Return a nice HTML page for browser requests
            return response()
                ->view('errors.rate-limited', [
                    'retryAfter' => $retryAfter,
                    'retryMinutes' => $retryMinutes,
                ], Response::HTTP_TOO_MANY_REQUESTS)
                ->header('Retry-After', $retryAfter)
                ->header('Cache-Control', 'no-store, no-cache, must-revalidate');
        }

        $this->limiter->hit($key, $decaySeconds);

        $response = $next($request);

        return $this->addRateLimitHeaders($response, $key, $maxAttempts);
    }

    /**
     * Check if this request should skip rate limiting.
     */
    private function shouldSkip(Request $request): bool
    {
        $path = $request->path();

        // Skip static assets
        if (preg_match('/\.(css|js|ico|png|jpg|jpeg|gif|svg|woff|woff2|ttf|eot)$/i', $path)) {
            return true;
        }

        // Skip Livewire update requests (they have their own protection)
        if ($request->hasHeader('X-Livewire')) {
            return true;
        }

        // Skip SEO files
        if (in_array($path, ['robots.txt', 'sitemap.xml'])) {
            return true;
        }

        return false;
    }

    /**
     * Generate a unique key for rate limiting.
     */
    private function resolveKey(Request $request): string
    {
        $userId = $request->user()?->id;

        if ($userId) {
            return 'global_rate_limit:user:' . $userId;
        }

        return 'global_rate_limit:ip:' . $request->ip();
    }

    /**
     * Add rate limit headers to response for frontend consumption.
     */
    private function addRateLimitHeaders(Response $response, string $key, int $maxAttempts): Response
    {
        $remaining = max(0, $maxAttempts - $this->limiter->attempts($key));
        $usagePercent = 1 - ($remaining / $maxAttempts);

        $response->headers->set('X-RateLimit-Limit', (string) $maxAttempts, false);
        $response->headers->set('X-RateLimit-Remaining', (string) $remaining, false);

        // Add warning header when user is approaching the limit
        if ($usagePercent >= self::WARNING_THRESHOLD) {
            $response->headers->set('X-RateLimit-Warning', 'true', false);
            $response->headers->set('X-RateLimit-Usage', (string) round($usagePercent * 100), false);
        }

        return $response;
    }
}
