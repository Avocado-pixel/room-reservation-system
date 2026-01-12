<?php

namespace App\Services\Security;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Services\Audit\AuditLogger;

/**
 * IP Blacklisting Service for DDoS and Abuse Protection.
 *
 * This service provides automatic IP blocking functionality based on repeated
 * rate limit violations. It uses Laravel's cache system for high-performance
 * lookups and automatic TTL-based expiration.
 *
 * Features:
 * - Automatic blacklisting after configurable threshold of abuse attempts
 * - Time-based ban expiration (no manual intervention required)
 * - Full audit trail of all blacklist operations
 * - Manual blacklist/unblacklist capabilities for admin intervention
 *
 * Usage:
 * ```php
 * // Check if IP is blocked
 * if ($blacklistService->isBlacklisted($ip)) {
 *     // Deny access
 * }
 *
 * // Record abuse (auto-blacklists after threshold)
 * $blacklistService->recordAbuseAttempt($ip);
 *
 * // Manual operations
 * $blacklistService->blacklist($ip, 60, 'manual_block');
 * $blacklistService->unblacklist($ip);
 * ```
 *
 * @see \App\Http\Middleware\CheckIpBlacklist For middleware integration
 * @see \App\Http\Middleware\AdvancedThrottle For rate limiting integration
 */
class IpBlacklistService
{
    /** @var string Cache key prefix for blacklisted IPs */
    private const BLACKLIST_PREFIX = 'ip_blacklist:';

    /** @var string Cache key prefix for abuse attempt counters */
    private const ABUSE_COUNTER_PREFIX = 'ip_abuse_counter:';

    /** @var int Default duration in minutes for IP bans */
    private const DEFAULT_BAN_DURATION_MINUTES = 30;

    /** @var int Number of rate limit violations before automatic blacklisting */
    private const ABUSE_THRESHOLD = 5;

    /**
     * Create a new IpBlacklistService instance.
     *
     * @param AuditLogger $auditLogger Service for audit trail logging
     */
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {
    }

    /**
     * Check if an IP address is currently blacklisted.
     *
     * This is a high-performance cache lookup operation suitable for
     * use in middleware on every request.
     *
     * @param string $ip The IP address to check
     * @return bool True if the IP is blacklisted, false otherwise
     */
    public function isBlacklisted(string $ip): bool
    {
        return Cache::has(self::BLACKLIST_PREFIX . $ip);
    }

    /**
     * Add an IP address to the blacklist.
     *
     * Creates a time-limited ban entry in the cache. The ban will
     * automatically expire after the specified duration.
     *
     * @param string $ip The IP address to blacklist
     * @param int $durationMinutes Ban duration in minutes (default: 30)
     * @param string $reason Human-readable reason for the ban (logged for audit)
     * @return void
     */
    public function blacklist(string $ip, int $durationMinutes = self::DEFAULT_BAN_DURATION_MINUTES, string $reason = 'rate_limit_exceeded'): void
    {
        $key = self::BLACKLIST_PREFIX . $ip;
        $expiresAt = now()->addMinutes($durationMinutes);

        Cache::put($key, [
            'reason' => $reason,
            'blacklisted_at' => now()->toIso8601String(),
            'expires_at' => $expiresAt->toIso8601String(),
        ], $expiresAt);

        $this->auditLogger->log('security.ip_blacklisted', [
            'ip' => $ip,
            'after' => [
                'reason' => $reason,
                'duration_minutes' => $durationMinutes,
                'expires_at' => $expiresAt->toIso8601String(),
            ],
        ]);

        Log::warning('IP blacklisted', [
            'ip' => $ip,
            'reason' => $reason,
            'duration_minutes' => $durationMinutes,
        ]);
    }

    /**
     * Remove an IP address from the blacklist.
     *
     * Also clears the abuse attempt counter for the IP to give
     * a fresh start.
     *
     * @param string $ip The IP address to unblacklist
     * @return bool True if the IP was previously blacklisted, false otherwise
     */
    public function unblacklist(string $ip): bool
    {
        $key = self::BLACKLIST_PREFIX . $ip;
        $existed = Cache::has($key);

        Cache::forget($key);
        Cache::forget(self::ABUSE_COUNTER_PREFIX . $ip);

        if ($existed) {
            $this->auditLogger->log('security.ip_unblacklisted', [
                'ip' => $ip,
            ]);
        }

        return $existed;
    }

    /**
     * Record an abuse attempt (rate limit violation).
     *
     * Increments the abuse counter for the IP. When the counter reaches
     * the threshold (default: 5), the IP is automatically blacklisted.
     * The counter expires after 10 minutes of inactivity.
     *
     * @param string $ip The IP address that triggered the rate limit
     * @param int|null $userId Optional user ID for audit logging
     * @return int The current abuse count after incrementing
     */
    public function recordAbuseAttempt(string $ip, ?int $userId = null): int
    {
        $key = self::ABUSE_COUNTER_PREFIX . $ip;
        $count = (int) Cache::get($key, 0) + 1;

        // Counter expires after 10 minutes of inactivity
        Cache::put($key, $count, now()->addMinutes(10));

        $this->auditLogger->log('security.rate_limit_hit', [
            'ip' => $ip,
            'user_id' => $userId,
            'after' => ['abuse_count' => $count],
        ]);

        if ($count >= self::ABUSE_THRESHOLD) {
            $this->blacklist($ip, self::DEFAULT_BAN_DURATION_MINUTES, 'repeated_rate_limit_violations');
            Cache::forget($key); // Reset counter after blacklisting
        }

        return $count;
    }

    /**
     * Get the remaining ban time for an IP address.
     *
     * @param string $ip The IP address to check
     * @return int Remaining minutes until ban expires (0 if not banned)
     */
    public function getRemainingBanMinutes(string $ip): int
    {
        $key = self::BLACKLIST_PREFIX . $ip;
        $data = Cache::get($key);

        if (!$data || !isset($data['expires_at'])) {
            return 0;
        }

        $expiresAt = \Carbon\Carbon::parse($data['expires_at']);
        if ($expiresAt->isPast()) {
            return 0;
        }

        return (int) now()->diffInMinutes($expiresAt);
    }

    /**
     * Get detailed blacklist information for an IP address.
     *
     * Returns an array with reason, blacklisted_at, and expires_at timestamps,
     * or null if the IP is not blacklisted.
     *
     * @param string $ip The IP address to query
     * @return array{reason: string, blacklisted_at: string, expires_at: string}|null
     */
    public function getBlacklistInfo(string $ip): ?array
    {
        return Cache::get(self::BLACKLIST_PREFIX . $ip);
    }
}
