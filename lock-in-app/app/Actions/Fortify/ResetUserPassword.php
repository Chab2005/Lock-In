<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\ResetsUserPasswords;

class ResetUserPassword implements ResetsUserPasswords
{
    use PasswordValidationRules;

    /**
     * Validate and reset the user's forgotten password.
     *
     * @param  array<string, string>  $input
     */
    public function reset(User $user, array $input): void
    {
        Validator::make($input, [
            'password' => $this->passwordRules(),
        ])->validate();

        // The vault key is derived from the login password via PBKDF2.
        // Changing the password makes the old vault key unrecoverable, so we clear vault credentials.
        $user->forceFill([
            'password' => $input['password'],
            'vault_salt' => null,
            'vault_kdf_params' => null,
            'vault_verifier' => null,
        ])->save();

        $user->vaultEntries()->delete();
    }
}
