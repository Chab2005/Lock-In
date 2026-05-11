<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laragear\WebAuthn\Contracts\WebAuthnAuthenticatable;
use Laragear\WebAuthn\WebAuthnAuthentication;
use Laravel\Fortify\TwoFactorAuthenticatable;

#[Fillable(['first_name', 'last_name', 'email', 'password', 'vault_salt', 'vault_kdf_params', 'vault_verifier',
    'is_2fa_email_enabled', 'email_otp_hash', 'email_otp_expires_at'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token',
    'vault_verifier', 'email_otp_hash'])]
class User extends Authenticatable implements MustVerifyEmail, WebAuthnAuthenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable, WebAuthnAuthentication;

    protected $appends = ['name'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'is_2fa_email_enabled' => 'boolean',
            'email_otp_expires_at' => 'datetime',
        ];
    }

    public function getNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function vaultEntries(): HasMany
    {
        return $this->hasMany(VaultEntry::class);
    }

    public function sharedEntries(): HasMany
    {
        return $this->hasMany(SharedEntry::class, 'owner_id');
    }

    public function receivedShares(): HasMany
    {
        return $this->hasMany(SharedEntry::class, 'recipient_id')
            ->where('status', 'active');
    }

    public function hasTwoFactorEnabled(): bool
    {
        return $this->two_factor_secret !== null && $this->two_factor_confirmed_at !== null;
    }

    public function hasEmailTwoFactorEnabled(): bool
    {
        return (bool) $this->is_2fa_email_enabled;
    }
}
