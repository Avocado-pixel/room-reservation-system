<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * International Tax ID Validator
 * 
 * Validates tax identification numbers from multiple countries:
 * - Spain: NIF, NIE, CIF
 * - Portugal: NIF
 * - USA: SSN, EIN
 * - UK: National Insurance Number
 * - Germany: Steuernummer, IdNr
 * - France: Numéro fiscal
 * - Italy: Codice Fiscale
 * - Generic: Alphanumeric 5-20 characters
 * 
 * Also checks for SQL injection and XSS patterns.
 */
class InternationalTaxId implements ValidationRule
{
    /**
     * Country-specific patterns
     */
    private const PATTERNS = [
        // Spain - NIF (8 digits + letter) or NIE (X/Y/Z + 7 digits + letter) or CIF (letter + 8 alphanumeric)
        'ES_NIF' => '/^[0-9]{8}[A-Z]$/i',
        'ES_NIE' => '/^[XYZ][0-9]{7}[A-Z]$/i',
        'ES_CIF' => '/^[ABCDEFGHJKLMNPQRSUVW][0-9]{7}[0-9A-J]$/i',
        
        // Portugal - NIF (9 digits, first digit 1-9)
        'PT_NIF' => '/^[1-9][0-9]{8}$/',
        
        // USA - SSN (XXX-XX-XXXX) or EIN (XX-XXXXXXX)
        'US_SSN' => '/^[0-9]{3}-?[0-9]{2}-?[0-9]{4}$/',
        'US_EIN' => '/^[0-9]{2}-?[0-9]{7}$/',
        
        // UK - National Insurance Number (AB123456C)
        'UK_NINO' => '/^[A-CEGHJ-PR-TW-Z]{2}[0-9]{6}[A-D]$/i',
        
        // Germany - Steuernummer (10-13 digits) or IdNr (11 digits)
        'DE_STEUERNR' => '/^[0-9]{10,13}$/',
        'DE_IDNR' => '/^[0-9]{11}$/',
        
        // France - Numéro fiscal (13 digits)
        'FR_NIF' => '/^[0-9]{13}$/',
        
        // Italy - Codice Fiscale (16 alphanumeric or 11 digits for companies)
        'IT_CF_PERSON' => '/^[A-Z]{6}[0-9]{2}[A-Z][0-9]{2}[A-Z][0-9]{3}[A-Z]$/i',
        'IT_CF_COMPANY' => '/^[0-9]{11}$/',
        
        // Brazil - CPF (11 digits) or CNPJ (14 digits)
        'BR_CPF' => '/^[0-9]{3}\.?[0-9]{3}\.?[0-9]{3}-?[0-9]{2}$/',
        'BR_CNPJ' => '/^[0-9]{2}\.?[0-9]{3}\.?[0-9]{3}\/?[0-9]{4}-?[0-9]{2}$/',
        
        // Generic alphanumeric (5-20 chars) - for countries not specifically listed
        'GENERIC' => '/^[A-Z0-9][A-Z0-9\-\.]{3,18}[A-Z0-9]$/i',
        
        // Generic numeric only (7-15 digits) - for simple numeric tax IDs
        'GENERIC_NUMERIC' => '/^[0-9]{7,15}$/',
    ];

    /**
     * Dangerous patterns for security
     */
    private const DANGEROUS_PATTERNS = [
        '/(\bunion\b|\bselect\b|\binsert\b|\bdelete\b|\bdrop\b)/i',
        '/(-{2}|;|\/\*|\*\/)/i',
        '/<[^>]*>/i',
        '/javascript\s*:/i',
        '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/',
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            $fail('The :attribute must be a string.');
            return;
        }

        // Normalize: remove extra spaces, convert to uppercase for comparison
        $value = strtoupper(trim($value));

        // Length check
        if (mb_strlen($value) < 5) {
            $fail('The :attribute must be at least 5 characters.');
            return;
        }

        if (mb_strlen($value) > 25) {
            $fail('The :attribute must not exceed 25 characters.');
            return;
        }

        // Security check
        foreach (self::DANGEROUS_PATTERNS as $pattern) {
            if (preg_match($pattern, $value)) {
                $fail('The :attribute contains invalid characters.');
                return;
            }
        }

        // Check against all known patterns
        foreach (self::PATTERNS as $country => $pattern) {
            if (preg_match($pattern, $value)) {
                // Additional validation for specific countries
                if (str_starts_with($country, 'ES_NIF') && !$this->validateSpainNif($value)) {
                    continue;
                }
                if (str_starts_with($country, 'ES_NIE') && !$this->validateSpainNie($value)) {
                    continue;
                }
                if (str_starts_with($country, 'PT_NIF') && !$this->validatePortugalNif($value)) {
                    continue;
                }
                
                // Valid!
                return;
            }
        }

        $fail('The :attribute must be a valid tax identification number.');
    }

    /**
     * Validate Spanish NIF checksum
     */
    private function validateSpainNif(string $value): bool
    {
        $letters = 'TRWAGMYFPDXBNJZSQVHLCKE';
        $number = (int) substr($value, 0, 8);
        $expectedLetter = $letters[$number % 23];
        $actualLetter = strtoupper(substr($value, -1));
        
        return $expectedLetter === $actualLetter;
    }

    /**
     * Validate Spanish NIE checksum
     */
    private function validateSpainNie(string $value): bool
    {
        $prefix = strtoupper(substr($value, 0, 1));
        $prefixMap = ['X' => '0', 'Y' => '1', 'Z' => '2'];
        
        if (!isset($prefixMap[$prefix])) {
            return false;
        }
        
        $normalizedValue = $prefixMap[$prefix] . substr($value, 1);
        return $this->validateSpainNif($normalizedValue);
    }

    /**
     * Validate Portuguese NIF checksum
     */
    private function validatePortugalNif(string $value): bool
    {
        if (!preg_match('/^[1-9][0-9]{8}$/', $value)) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 8; $i++) {
            $sum += (int) $value[$i] * (9 - $i);
        }
        
        $remainder = $sum % 11;
        $checkDigit = ($remainder < 2) ? 0 : 11 - $remainder;
        
        return (int) $value[8] === $checkDigit;
    }
}
