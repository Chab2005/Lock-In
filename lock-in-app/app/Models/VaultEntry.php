<?php

namespace App\Models;

use Database\Factories\VaultEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'website', 'nickname', 'email_hint', 'icon', 'encrypted_password', 'iv', 'notes'])]
class VaultEntry extends Model
{
    /** @use HasFactory<VaultEntryFactory> */
    use HasFactory;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
