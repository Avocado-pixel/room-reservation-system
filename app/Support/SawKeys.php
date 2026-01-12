<?php

namespace App\Support;

final class SawKeys
{
    /**
     * Devolve a APP_KEY em formato apropriado para HMAC, alinhado com o legacy.
     */
    public static function hmacKey(): string
    {
        $key = (string) config('app.key', '');

        if (str_starts_with($key, 'base64:')) {
            $decoded = base64_decode(substr($key, 7), true);
            if ($decoded !== false) {
                return $decoded;
            }
        }

        return $key;
    }
}
