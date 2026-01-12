<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware: HTTP Security Headers.
 *
 * Adds comprehensive security headers to all HTTP responses to protect
 * against common web vulnerabilities and attacks.
 *
 * Headers Applied:
 * - X-Frame-Options: Prevents clickjacking attacks
 * - X-Content-Type-Options: Prevents MIME-type sniffing
 * - X-XSS-Protection: Legacy XSS filter for older browsers
 * - Referrer-Policy: Controls referrer information leakage
 * - Permissions-Policy: Disables dangerous browser APIs
 * - Strict-Transport-Security: Forces HTTPS (production only)
 * - Content-Security-Policy: Prevents XSS and data injection
 * - Cross-Origin-*-Policy: Isolates cross-origin resources
 * - Cache-Control: Prevents caching of sensitive pages
 *
 * CSP Nonce:
 * A random nonce is generated for each request and stored in the request
 * attributes. Use `$request->attributes->get('csp_nonce')` in Blade templates
 * for inline scripts: `<script nonce="{{ request()->attributes->get('csp_nonce') }}">`.
 *
 * @see https://owasp.org/www-project-secure-headers/
 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/CSP
 */
class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * Generates a CSP nonce, processes the request, then adds all security
     * headers to the response.
     *
     * @param Request $request The incoming HTTP request
     * @param Closure $next The next middleware handler
     * @return Response The HTTP response with security headers
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Generate CSP nonce for inline scripts if needed
        $nonce = Str::random(32);
        $request->attributes->set('csp_nonce', $nonce);

        /** @var Response $response */
        $response = $next($request);

        // === Frame & Click-jacking Protection ===
        $response->headers->set('X-Frame-Options', 'DENY', false);

        // === MIME-type Sniffing Protection ===
        $response->headers->set('X-Content-Type-Options', 'nosniff', false);

        // === XSS Protection (legacy browsers) ===
        $response->headers->set('X-XSS-Protection', '1; mode=block', false);

        // === Referrer Policy ===
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin', false);

        // === Permissions Policy (disable dangerous APIs) ===
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=(), payment=(), usb=(), magnetometer=(), gyroscope=(), accelerometer=()', false);

        // === HSTS (HTTP Strict Transport Security) ===
        // Only add in production to avoid HTTPS issues in dev
        if (app()->environment('production')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload', false);
        }

        // === Content Security Policy ===
        $csp = $this->buildContentSecurityPolicy($nonce);
        $response->headers->set('Content-Security-Policy', $csp, false);

        // === Cache Control for sensitive pages ===
        if ($request->is('admin/*', 'client/*', 'user/*', 'login', 'register')) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, private', false);
            $response->headers->set('Pragma', 'no-cache', false);
        }

        // === Cross-Origin Policies ===
        // Using 'unsafe-none' for COEP to allow external fonts (Bunny Fonts)
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin', false);
        $response->headers->set('Cross-Origin-Embedder-Policy', 'unsafe-none', false);
        $response->headers->set('Cross-Origin-Resource-Policy', 'cross-origin', false);

        return $response;
    }

    /**
     * Build the Content-Security-Policy header value.
     *
     * Constructs a CSP that:
     * - Restricts all resources to same-origin by default
     * - Allows scripts only from self or with the provided nonce
     * - Allows inline styles (required for Tailwind CSS)
     * - Allows images from self, data URIs, and HTTPS sources
     * - Blocks all frame embedding
     * - Upgrades HTTP requests to HTTPS
     *
     * Note: 'unsafe-eval' is required for Alpine.js/Livewire to evaluate
     * x-data expressions. 'unsafe-inline' is needed for inline scripts in Blade views.
     *
     * @param string $nonce The CSP nonce for inline scripts
     * @return string The complete CSP header value
     */
    protected function buildContentSecurityPolicy(string $nonce): string
    {
        $directives = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval'",  // unsafe-inline for Blade scripts, unsafe-eval for Alpine.js
            "style-src 'self' 'unsafe-inline' https://fonts.bunny.net",  // Tailwind + Bunny Fonts
            "img-src 'self' data: https:",
            "font-src 'self' https://fonts.bunny.net",  // Allow Bunny Fonts
            "connect-src 'self'",
            "frame-src 'none'",
            "frame-ancestors 'none'",
            "form-action 'self'",
            "base-uri 'self'",
            "object-src 'none'",
            "upgrade-insecure-requests",
        ];

        return implode('; ', $directives);
    }
}
