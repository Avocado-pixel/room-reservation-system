<?php

namespace App\Actions\Fortify;

use App\Models\User;
use App\Notifications\AccountVerificationOptions;
use App\Rules\InternationalTaxId;
use App\Rules\SafePersonName;
use App\Support\SawKeys;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;

class UpdateUserProfileInformation implements UpdatesUserProfileInformation
{
    /**
     * Validate and update the given user's profile information.
     *
     * @param  array<string, mixed>  $input
     */
    public function update(User $user, array $input): void
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'min:2', 'max:100', new SafePersonName],
            'email' => ['required', 'email:rfc,dns', 'max:255', Rule::unique('users')->ignore($user->id)],
            'tax_id' => ['nullable', 'string', new InternationalTaxId, Rule::unique('users', 'tax_id')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'min:6', 'max:25', 'phone:AUTO'],
            'phone_country' => ['nullable', 'string', 'size:2'],
            'address' => ['nullable', 'string', 'min:5', 'max:255'],
        ], [
            'phone.phone' => 'Please enter a valid phone number for the selected country.',
        ])->validateWithBag('updateProfileInformation');

       

        $emailChanged = (string) $input['email'] !== (string) $user->email;

        // Use phone as-is - it comes from intl-tel-input in E.164 format
        $phone = $input['phone'] ?? $user->phone;

        $user->forceFill([
            'name' => $this->sanitizeName($input['name']),
            'email' => strtolower(trim($input['email'])),
            'tax_id' => isset($input['tax_id']) ? strtoupper(trim($input['tax_id'])) : $user->tax_id,
            'phone' => $phone,
            'phone_country' => isset($input['phone_country']) ? strtoupper($input['phone_country']) : $user->phone_country,
            'address' => isset($input['address']) ? trim($input['address']) : $user->address,
            // Keep account active/verified when email changes.
            'email_validation_token' => $emailChanged ? null : $user->email_validation_token,
            'email_validation_expires_at' => $emailChanged ? null : $user->email_validation_expires_at,
        ])->save();
    }

    /**
     * Sanitize name by normalizing whitespace and proper casing.
     */
    private function sanitizeName(string $name): string
    {
        $name = preg_replace('/\s+/', ' ', trim($name));
        
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
