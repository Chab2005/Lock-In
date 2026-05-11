<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShareAuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'shared_entry_id', 'actor_id', 'event', 'ip_address', 'user_agent',
    ];

    public function sharedEntry(): BelongsTo
    {
        return $this->belongsTo(SharedEntry::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
