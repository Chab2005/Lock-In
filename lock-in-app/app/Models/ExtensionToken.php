<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExtensionToken extends Model
{
    protected $fillable = ['user_id', 'token_hash'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function hashToken(string $rawToken): string
    {
        return hash('sha256', $rawToken);
    }
}
