<?php

namespace Database\Factories;

use App\Models\SharedEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SharedEntry>
 */
class SharedEntryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'owner_id' => User::factory(),
            'recipient_id' => null,
            'recipient_email' => fake()->safeEmail(),
            'label' => fake()->word(),
            'encrypted_payload' => base64_encode(fake()->sha256()),
            'iv' => base64_encode(random_bytes(12)),
            'share_token_hash' => hash('sha256', random_bytes(32)),
            'status' => 'active',
        ];
    }
}
