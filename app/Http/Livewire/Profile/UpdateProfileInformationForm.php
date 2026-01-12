<?php

namespace App\Http\Livewire\Profile;

use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;
use Laravel\Jetstream\ConfirmsPasswords;
use Laravel\Jetstream\Http\Livewire\UpdateProfileInformationForm as BaseUpdateProfileInformationForm;

class UpdateProfileInformationForm extends BaseUpdateProfileInformationForm
{
    use ConfirmsPasswords;

    /**
     * Update the user's profile information, then clear sensitive transient data.
     */
    public function updateProfileInformation(UpdatesUserProfileInformation $updater)
    {
        $response = parent::updateProfileInformation($updater);

        // Do not keep the confirmed password in Livewire state after use.
        unset($this->state['current_password']);

        // Require re-confirmation for the next sensitive action.
        $this->resetPasswordConfirmation();

        return $response;
    }

    /**
     * Force the next sensitive action to prompt for password again.
     */
    protected function resetPasswordConfirmation(): void
    {
        session()->forget('auth.password_confirmed_at');
    }
}
