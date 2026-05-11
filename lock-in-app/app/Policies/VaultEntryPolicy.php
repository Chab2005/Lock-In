<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VaultEntry;

class VaultEntryPolicy
{
    public function update(User $user, VaultEntry $entry): bool
    {
        return $user->id === $entry->user_id;
    }

    public function delete(User $user, VaultEntry $entry): bool
    {
        return $user->id === $entry->user_id;
    }
}
