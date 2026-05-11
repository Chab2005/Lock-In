<?php

namespace App\Models;

use Database\Factories\SharedEntryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SharedEntry extends Model
{
    /** @use HasFactory<SharedEntryFactory> */
    use HasFactory;

    protected $fillable = [
        'owner_id', 'recipient_id', 'recipient_email', 'label',
        'encrypted_payload', 'iv', 'share_token_hash', 'status',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(ShareAuditLog::class);
    }

    public function isRevoked(): bool
    {
        return $this->status === 'revoked';
    }
}
