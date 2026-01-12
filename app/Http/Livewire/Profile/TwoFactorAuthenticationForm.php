<?php

namespace App\Http\Livewire\Profile;

use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;
use Laravel\Jetstream\Http\Livewire\TwoFactorAuthenticationForm as BaseTwoFactorAuthenticationForm;

class TwoFactorAuthenticationForm extends BaseTwoFactorAuthenticationForm
{
    /**
     * Enable two-factor authentication and force a fresh password confirmation next time.
     */
    public function enableTwoFactorAuthentication(EnableTwoFactorAuthentication $enable): void
    {
        parent::enableTwoFactorAuthentication($enable);
    }

    /**
     * Show recovery codes and force re-confirmation next time.
     */
    public function showRecoveryCodes(): void
    {
        parent::showRecoveryCodes();
    }

    /**
     * Regenerate recovery codes and force re-confirmation next time.
     */
    public function regenerateRecoveryCodes(GenerateNewRecoveryCodes $generate): void
    {
        parent::regenerateRecoveryCodes($generate);

        $this->resetPasswordConfirmation();
    }

    /**
     * Confirm two-factor authentication and force re-confirmation next time.
     */
    public function confirmTwoFactorAuthentication(ConfirmTwoFactorAuthentication $confirm): void
    {
        parent::confirmTwoFactorAuthentication($confirm);

        $this->resetPasswordConfirmation();
    }

    /**
     * Disable two-factor authentication and force re-confirmation next time.
     */
    public function disableTwoFactorAuthentication(DisableTwoFactorAuthentication $disable): void
    {
        parent::disableTwoFactorAuthentication($disable);

        $this->resetPasswordConfirmation();
    }

    /**
     * Force the next sensitive action to prompt for password again.
     */
    protected function resetPasswordConfirmation(): void
    {
        session()->forget('auth.password_confirmed_at');
    }
}
