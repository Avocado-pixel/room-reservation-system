<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates a person's name with security and logic checks.
 * 
 * Allows:
 * - Letters (including Unicode: á, é, ñ, ç, etc.)
 * - Spaces, hyphens, apostrophes (O'Connor, Mary-Jane)
 * - Minimum 2 characters, maximum 100
 * 
 * Blocks:
 * - Numbers
 * - SQL injection patterns
 * - XSS patterns (<script>, javascript:, etc.)
 * - Special characters that could be dangerous
 * - Excessive spaces or repeated special chars
 */
class SafePersonName implements ValidationRule
{
    /**
     * Dangerous patterns that indicate potential attacks
     */
    private const DANGEROUS_PATTERNS = [
        // SQL Injection patterns
        '/(\bunion\b|\bselect\b|\binsert\b|\bupdate\b|\bdelete\b|\bdrop\b|\btruncate\b)/i',
        '/(-{2}|;|\/\*|\*\/)/i',  // SQL comments
        '/(\bor\b|\band\b)\s*[\d\'\"=]/i',  // OR/AND injection
        
        // XSS patterns
        '/<[^>]*>/i',  // HTML tags
        '/javascript\s*:/i',
        '/on\w+\s*=/i',  // Event handlers (onclick=, onerror=, etc.)
        '/data\s*:/i',
        
        // Path traversal
        '/\.{2,}[\/\\\\]/i',
        
        // Null bytes
        '/\x00/',
        
        // Control characters
        '/[\x01-\x08\x0B\x0C\x0E-\x1F\x7F]/',
    ];

    /**
     * Valid name pattern: letters (Unicode), spaces, hyphens, apostrophes, periods
     */
    private const VALID_NAME_PATTERN = '/^[\p{L}\p{M}\s\'\-\.]+$/u';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            $fail('The :attribute must be a string.');
            return;
        }

        $value = trim($value);

        // Length check
        if (mb_strlen($value) < 2) {
            $fail('The :attribute must be at least 2 characters.');
            return;
        }

        if (mb_strlen($value) > 100) {
            $fail('The :attribute must not exceed 100 characters.');
            return;
        }

        // Check for dangerous patterns
        foreach (self::DANGEROUS_PATTERNS as $pattern) {
            if (preg_match($pattern, $value)) {
                $fail('The :attribute contains invalid characters.');
                return;
            }
        }

        // Must match valid name pattern (letters, spaces, hyphens, apostrophes, dots)
        if (!preg_match(self::VALID_NAME_PATTERN, $value)) {
            $fail('The :attribute may only contain letters, spaces, hyphens, apostrophes, and periods.');
            return;
        }

        // No excessive repeated characters (e.g., "aaaaaaa")
        if (preg_match('/(.)\1{4,}/u', $value)) {
            $fail('The :attribute contains too many repeated characters.');
            return;
        }

        // No excessive spaces
        if (preg_match('/\s{3,}/', $value)) {
            $fail('The :attribute contains too many consecutive spaces.');
            return;
        }

        // Must contain at least one letter
        if (!preg_match('/\p{L}/u', $value)) {
            $fail('The :attribute must contain at least one letter.');
            return;
        }

        // Name should have logical structure (not just symbols)
        $letterCount = preg_match_all('/\p{L}/u', $value);
        $totalLength = mb_strlen(preg_replace('/\s/', '', $value));
        if ($totalLength > 0 && ($letterCount / $totalLength) < 0.5) {
            $fail('The :attribute must be a valid name.');
            return;
        }
    }
}
