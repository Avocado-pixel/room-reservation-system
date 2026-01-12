<?php

namespace App\Services\Audit;

use App\Models\AuditLog;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AuditLogger
{
    /**
     * Sensitive field names that should be masked in logs.
     */
    private const SENSITIVE_FIELDS = [
        'password',
        'password_confirmation',
        'current_password',
        'new_password',
        'token',
        'api_token',
        'secret',
        'credit_card',
        'card_number',
        'cvv',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * Log an audit event to the database.
     * Logs are immutable - no update or delete methods are exposed.
     */
    public function log(string $action, array $context = []): void
    {
        $before = Arr::get($context, 'before');
        $after = Arr::get($context, 'after');

        $payload = [
            'action' => $action,
            'user_id' => Arr::get($context, 'user_id', Auth::id()),
            'ip' => $this->maskIpForPrivacy(Arr::get($context, 'ip', request()->ip())),
            'user_agent' => substr((string) Arr::get($context, 'user_agent', request()->userAgent()), 0, 255),
            'subject_type' => Arr::get($context, 'subject_type'),
            'subject_id' => Arr::get($context, 'subject_id'),
            'before' => $before ? $this->maskSensitiveData($before) : null,
            'after' => $after ? $this->maskSensitiveData($after) : null,
        ];

        try {
            AuditLog::create($payload);
        } catch (\Throwable $e) {
            Log::warning('audit_log_failed', [
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Mask sensitive fields in data before logging.
     *
     * @param array<string, mixed>|null $data
     * @return array<string, mixed>|null
     */
    private function maskSensitiveData(?array $data): ?array
    {
        if ($data === null) {
            return null;
        }

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->maskSensitiveData($value);
            } elseif ($this->isSensitiveField($key)) {
                $data[$key] = '[REDACTED]';
            }
        }

        return $data;
    }

    /**
     * Check if a field name is sensitive.
     */
    private function isSensitiveField(string $field): bool
    {
        $lowerField = strtolower($field);
        foreach (self::SENSITIVE_FIELDS as $sensitive) {
            if (str_contains($lowerField, $sensitive)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Partially mask IP address for privacy compliance.
     * Keeps first two octets for geo analysis, masks last two.
     */
    private function maskIpForPrivacy(?string $ip): ?string
    {
        if ($ip === null) {
            return null;
        }

        // For local development, don't mask
        if ($ip === '127.0.0.1' || $ip === '::1') {
            return $ip;
        }

        // IPv4: mask last two octets
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $ip);
            if (count($parts) === 4) {
                return $parts[0] . '.' . $parts[1] . '.xxx.xxx';
            }
        }

        // IPv6: mask last 64 bits
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $parts = explode(':', $ip);
            $halfCount = (int) ceil(count($parts) / 2);
            return implode(':', array_slice($parts, 0, $halfCount)) . ':xxxx:xxxx:xxxx:xxxx';
        }

        return $ip;
    }
}
