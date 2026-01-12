<?php

namespace App\Actions\Fortify;

use App\Models\User;
use App\Notifications\AccountVerificationOptions;
use App\Rules\InternationalTaxId;
use App\Rules\SafePersonName;
use App\Support\SawKeys;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Jetstream\Jetstream;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        // Get the full international phone number if available
        $phone = $input['phone_full'] ?? $input['phone'] ?? '';
        $phoneCountry = $input['phone_country'] ?? 'ES';

        Validator::make($input, [
            'name' => ['required', 'string', 'min:2', 'max:100', new SafePersonName],
            'email' => ['required', 'string', 'email:rfc,dns', 'max:255', 'unique:users'],
            'tax_id' => ['required', 'string', new InternationalTaxId, 'unique:users,tax_id'],
            'phone' => ['required', 'string', 'min:6', 'max:25', 'phone:AUTO'],
            'phone_full' => ['nullable', 'string', 'max:25'],
            'phone_country' => ['nullable', 'string', 'size:2'],
            'address' => ['required', 'string', 'min:5', 'max:255'],
            'password' => $this->passwordRules(),
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature() ? ['accepted', 'required'] : '',
        ], [
            'name.required' => 'Please enter your full name.',
            'name.min' => 'Name must be at least 2 characters.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'This email is already registered.',
            'tax_id.unique' => 'This Tax ID is already registered.',
            'phone.min' => 'Please enter a valid phone number.',
            'phone.phone' => 'Please enter a valid phone number for the selected country.',
            'address.min' => 'Please enter a complete address.',
        ])->validate();

        // Use the full E.164 number from intl-tel-input (already formatted correctly)
        // If phone_full is available, use it directly; otherwise use phone as-is
        $normalizedPhone = !empty($input['phone_full']) ? $input['phone_full'] : $phone;

        // Validation code (6 digits) + HMAC (do not store the code in plain text)
        $code = (string) random_int(100000, 999999);
        $codeHash = hash_hmac('sha256', $code, SawKeys::hmacKey());

        $user = User::create([
            'name' => $this->sanitizeName($input['name']),
            'email' => strtolower(trim($input['email'])),
            'tax_id' => strtoupper(trim($input['tax_id'])),
            'phone' => $normalizedPhone,
            'phone_country' => strtoupper($phoneCountry),
            'address' => trim($input['address']),
            'role' => 'user',
            'status' => 'pending',
            'email_validation_token' => $codeHash,
            'email_validation_expires_at' => now()->addMinutes(30),
            'password' => Hash::make($input['password']),
        ]);

        // Send ONE email that contains both options: link + code.
        $user->notify(new AccountVerificationOptions($code));

        return $user;
    }

    /**
     * Sanitize name by normalizing whitespace and proper casing.
     */
    private function sanitizeName(string $name): string
    {
        // Normalize multiple spaces to single space
        $name = preg_replace('/\s+/', ' ', trim($name));
        
        // Convert to title case while preserving particles like "de", "van", "von"
        $words = explode(' ', mb_strtolower($name));
        $particles = ['de', 'da', 'do', 'dos', 'das', 'van', 'von', 'der', 'den', 'la', 'le', 'du'];
        
        $result = [];
        foreach ($words as $index => $word) {
            if ($index === 0 || !in_array($word, $particles)) {
                $result[] = mb_ucfirst($word);
            } else {
                $result[] = $word;
            }
        }
        
        return implode(' ', $result);
    }
}
