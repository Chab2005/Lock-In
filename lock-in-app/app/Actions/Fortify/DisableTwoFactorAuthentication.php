<?php

namespace App\Actions\Fortify;

use App\Models\User;
use UnexpectedValueException;

class DisableTwoFactorAuthentication extends \Laravel\Fortify\Actions\DisableTwoFactorAuthentication
{
    /**
     * Disable TOTP for the given user.
     *
     * Disabling is only allowed when email OTP is active — otherwise the account
     * would have no MFA method at all. The controller upstream renders 422 for
     * JSON callers when an HttpException is thrown here.
     */
    public function __invoke(mixed $user): void
    {
        if (! $user instanceof User) {
            throw new UnexpectedValueException('Expected an App\\Models\\User instance.');
        }

        // Block: disabling TOTP would leave the account with zero MFA methods.
        if (! $user->hasEmailTwoFactorEnabled()) {
            abort(422, 'You must re-enable email OTP before removing your authenticator app.');
        }

        // Delegate to parent: handles the DB update and dispatches
        // TwoFactorAuthenticationDisabled event.
        parent::__invoke($user);
    }
}
