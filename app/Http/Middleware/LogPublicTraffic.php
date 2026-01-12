<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware: Ultra-Lightweight Public Traffic Logger.
 *
 * Records anonymous traffic to public routes for security analysis
 * and bot/scanner detection. Designed for minimal performance impact.
 *
 * Features:
 * - Batch logging: Accumulates entries in cache, writes in batches of 50
 * - Automatic flush: Writes if batch is 60+ seconds old
 * - Minimal data: Only IP, timestamp, URL, method
 * - Pattern exclusion: Skips static assets and internal routes
 * - Bot detection: Flags suspicious paths (.env, wp-admin, etc.)
 * - Volume alerts: Warns on IPs with >20 requests per batch
 *
 * Log Channels:
 * - public_traffic: All traffic entries (30-day retention)
 * - security: Scanner detections and high-volume alerts (90-day retention)
 *
 * Performance:
 * - Cache read/write per request: O(1)
 * - Disk I/O: Only on batch flush (every ~50 requests)
 * - Memory: Minimal (small array in cache)
 *
 * @see config/logging.php For log channel configuration
 */
class LogPublicTraffic
{
    /** @var string Cache key for the traffic batch */
    private const BATCH_KEY = 'public_traffic_batch';

    /** @var int Number of entries before batch is flushed to disk */
    private const BATCH_SIZE = 50;

    /** @var int Maximum age in seconds before forced batch flush */
    private const BATCH_TTL = 60;

    /**
     * URL patterns that should not be logged.
     *
     * Static assets and internal framework routes are excluded
     * to reduce noise in the logs.
     *
     * @var array<int, string>
     */
    private const EXCLUDED_PATTERNS = [
        '/assets/',
        '/build/',
        '/storage/',
        '/favicon.ico',
        '/robots.txt',
        '/_debugbar',
        '/livewire/',
    ];

    /**
     * Handle an incoming request.
     *
     * Logs the request to the traffic batch if it's from an
     * unauthenticated user and not to an excluded path.
     *
     * @param Request $request The incoming HTTP request
     * @param Closure $next The next middleware handler
     * @return Response The HTTP response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only log public routes (unauthenticated requests)
        if ($request->user() !== null) {
            return $next($request);
        }

        // Skip excluded patterns
        $path = $request->path();
        foreach (self::EXCLUDED_PATTERNS as $pattern) {
            if (str_contains('/' . $path, $pattern)) {
                return $next($request);
            }
        }

        // Add to batch
        $this->addToBatch([
            'ip' => $request->ip(),
            'url' => substr($request->fullUrl(), 0, 500),
            'method' => $request->method(),
            'ts' => now()->toIso8601String(),
        ]);

        return $next($request);
    }

    /**
     * Add an entry to the traffic batch.
     *
     * Stores the entry in cache and triggers a flush if the batch
     * has reached its size limit or age threshold.
     *
     * @param array{ip: string, url: string, method: string, ts: string} $entry Traffic entry data
     * @return void
     */
    private function addToBatch(array $entry): void
    {
        $batch = Cache::get(self::BATCH_KEY, [
            'entries' => [],
            'created_at' => now()->timestamp,
        ]);

        $batch['entries'][] = $entry;

        // Flush if batch is full or old
        $age = now()->timestamp - ($batch['created_at'] ?? now()->timestamp);
        if (count($batch['entries']) >= self::BATCH_SIZE || $age >= self::BATCH_TTL) {
            $this->flushBatch($batch['entries']);
            Cache::forget(self::BATCH_KEY);
        } else {
            Cache::put(self::BATCH_KEY, $batch, self::BATCH_TTL + 10);
        }
    }

    /**
     * Flush the traffic batch to log storage.
     *
     * Writes all accumulated entries to the public_traffic log channel
     * and analyzes for suspicious patterns.
     *
     * @param array<int, array{ip: string, url: string, method: string, ts: string}> $entries Traffic entries to flush
     * @return void
     */
    private function flushBatch(array $entries): void
    {
        if (empty($entries)) {
            return;
        }

        // Log to dedicated channel (file-based for speed)
        Log::channel('public_traffic')->info('batch', [
            'count' => count($entries),
            'entries' => $entries,
        ]);

        // Detect potential bot scanning patterns
        $this->detectScanningPatterns($entries);
    }

    /**
     * Detect potential bot or scanner activity in traffic patterns.
     *
     * Checks for:
     * - Requests to known vulnerability scanner paths (.env, wp-admin, etc.)
     * - IPs with unusually high request counts (>20 per batch)
     *
     * Suspicious activity is logged to the security channel.
     *
     * @param array<int, array{ip: string, url: string, method: string, ts: string}> $entries Traffic entries to analyze
     * @return void
     */
    private function detectScanningPatterns(array $entries): void
    {
        /** @var array<string, int> $ipCounts IP address request counts */
        $ipCounts = [];

        /** @var array<int, string> $suspiciousPaths Known scanner/vulnerability paths */
        $suspiciousPaths = [
            '.env', '.git', 'wp-admin', 'wp-login', 'phpmyadmin',
            'admin.php', 'xmlrpc.php', 'config.php', '.sql', 'backup',
        ];

        foreach ($entries as $entry) {
            $ip = $entry['ip'] ?? 'unknown';
            $url = strtolower($entry['url'] ?? '');

            // Count requests per IP
            $ipCounts[$ip] = ($ipCounts[$ip] ?? 0) + 1;

            // Check for suspicious paths
            foreach ($suspiciousPaths as $pattern) {
                if (str_contains($url, $pattern)) {
                    Log::channel('security')->warning('scanner_detected', [
                        'ip' => $ip,
                        'url' => $url,
                        'pattern' => $pattern,
                    ]);
                    break;
                }
            }
        }

        // Flag IPs with unusually high request counts
        foreach ($ipCounts as $ip => $count) {
            if ($count > 20) {
                Log::channel('security')->warning('high_request_volume', [
                    'ip' => $ip,
                    'count' => $count,
                    'period' => 'batch',
                ]);
            }
        }
    }
}
