<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware: Input Sanitization (XSS & SQL Injection Defense).
 *
 * Provides defense-in-depth protection against common injection attacks
 * by sanitizing all user input before it reaches the application.
 *
 * Features:
 * - Removes HTML tags from untrusted input (prevents XSS)
 * - Removes null bytes (prevents null byte injection)
 * - Filters potential SQL injection patterns (defense in depth)
 * - Preserves password fields unchanged (for proper hashing)
 * - Allows limited safe HTML for admin users in content fields
 *
 * Security Notes:
 * - This is a defense-in-depth measure; Eloquent already uses prepared statements
 * - Always validate and escape output at the view layer as well
 * - Password fields are excluded to preserve the original input for hashing
 *
 * @see https://owasp.org/www-community/attacks/xss/
 * @see https://owasp.org/www-community/attacks/SQL_Injection
 */
class SanitizeInput
{
    /**
     * Fields that should never be sanitized.
     *
     * Passwords must remain unchanged for proper hash verification.
     * Tokens are typically random strings that don't need sanitization.
     *
     * @var array<int, string>
     */
    private const EXCLUDED_FIELDS = [
        'password',
        'password_confirmation',
        'current_password',
        '_token',
        'token',
        'api_token',
        'remember_token',
    ];

    /**
     * Fields that may contain HTML (admin-only content fields).
     *
     * These fields allow a limited set of safe HTML tags when the
     * authenticated user is an admin.
     *
     * @var array<int, string>
     */
    private const HTML_ALLOWED_FIELDS = [
        'description',
        'usage_rules',
        'content',
        'body',
    ];

    /**
     * Handle an incoming request.
     *
     * Sanitizes all input data in the request before passing to the next handler.
     *
     * @param Request $request The incoming HTTP request
     * @param Closure $next The next middleware handler
     * @return Response The HTTP response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $this->sanitizeInputData($request);

        return $next($request);
    }

    /**
     * Sanitize all input data in the request.
     *
     * @param Request $request The request containing input to sanitize
     * @return void
     */
    protected function sanitizeInputData(Request $request): void
    {
        $input = $request->all();
        $sanitized = $this->sanitizeArray($input, $request->user()?->isAdmin() ?? false);
        $request->merge($sanitized);
    }

    /**
     * Recursively sanitize an array of input data.
     *
     * @param array<string, mixed> $data The input data array
     * @param bool $isAdmin Whether the current user is an admin
     * @return array<string, mixed> The sanitized data array
     */
    protected function sanitizeArray(array $data, bool $isAdmin): array
    {
        $sanitized = [];

        foreach ($data as $key => $value) {
            if (in_array($key, self::EXCLUDED_FIELDS, true)) {
                $sanitized[$key] = $value;
                continue;
            }

            if (is_array($value)) {
                $sanitized[$key] = $this->sanitizeArray($value, $isAdmin);
            } elseif (is_string($value)) {
                $sanitized[$key] = $this->sanitizeString($key, $value, $isAdmin);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Sanitize a single string value.
     *
     * Applies the appropriate sanitization based on field type and user role:
     * - Null bytes are always removed
     * - Whitespace is trimmed
     * - HTML is stripped (or limited for admin content fields)
     * - SQL injection patterns are filtered
     *
     * @param string $key The field name
     * @param string $value The field value
     * @param bool $isAdmin Whether the current user is an admin
     * @return string The sanitized string
     */
    protected function sanitizeString(string $key, string $value, bool $isAdmin): string
    {
        // Remove null bytes (potential security issue)
        $value = str_replace("\0", '', $value);

        // Trim whitespace
        $value = trim($value);

        // Allow limited HTML for admins in specific fields
        if ($isAdmin && in_array($key, self::HTML_ALLOWED_FIELDS, true)) {
            return $this->sanitizeHtmlForAdmin($value);
        }

        // Strip all HTML tags for regular input
        $value = strip_tags($value);

        // Encode HTML entities to prevent XSS
        $value = htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8', false);

        // Decode back safe characters (letters, numbers, basic punctuation)
        $value = htmlspecialchars_decode($value, ENT_QUOTES);

        // Remove potential SQL injection patterns (defense in depth - Eloquent already uses prepared statements)
        $value = $this->removeSqlPatterns($value);

        return $value;
    }

    /**
     * Sanitize HTML content for admin users.
     *
     * Allows a whitelist of safe HTML tags while removing dangerous
     * attributes and JavaScript protocols.
     *
     * Allowed tags: p, br, strong, b, em, i, u, ul, ol, li, h1-h6, a, span, div
     *
     * @param string $value The HTML content to sanitize
     * @return string The sanitized HTML
     */
    protected function sanitizeHtmlForAdmin(string $value): string
    {
        // Allow only safe HTML tags for admin content
        $allowedTags = '<p><br><strong><b><em><i><u><ul><ol><li><h1><h2><h3><h4><h5><h6><a><span><div>';
        $value = strip_tags($value, $allowedTags);

        // Remove dangerous attributes (onclick, onload, etc.)
        $value = preg_replace('/\s*on\w+\s*=\s*["\'][^"\']*["\']/i', '', $value);
        $value = preg_replace('/\s*javascript\s*:/i', '', $value);
        $value = preg_replace('/\s*data\s*:/i', '', $value);
        $value = preg_replace('/\s*vbscript\s*:/i', '', $value);

        return $value;
    }

    /**
     * Remove common SQL injection patterns from a string.
     *
     * This is a defense-in-depth measure. Laravel's Eloquent ORM already
     * uses prepared statements, which fully prevent SQL injection. This
     * additional layer catches edge cases and provides logging opportunities.
     *
     * Filtered patterns:
     * - SQL single-line comments (-- and #)
     * - SQL block comments
     * - Chained dangerous statements (DROP, DELETE, TRUNCATE, etc.)
     *
     * @param string $value The string to filter
     * @return string The filtered string
     */
    protected function removeSqlPatterns(string $value): string
    {
        // Defense in depth: remove common SQL injection patterns
        // Note: Eloquent's prepared statements already prevent SQL injection
        $patterns = [
            '/(\-\-|\#).*$/m',           // SQL comments
            '/\/\*.*?\*\//s',            // Block comments
            '/;\s*(DROP|DELETE|TRUNCATE|ALTER|CREATE|INSERT|UPDATE)\s/i',
        ];

        foreach ($patterns as $pattern) {
            $value = preg_replace($pattern, '', $value);
        }

        return $value;
    }
}
