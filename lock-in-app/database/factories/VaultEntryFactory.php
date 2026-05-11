<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\VaultEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VaultEntry>
 */
class VaultEntryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'website' => fake()->url(),
            'nickname' => fake()->word(),
            'email_hint' => fake()->safeEmail(),
            'encrypted_password' => base64_encode(fake()->sha256()),
            'iv' => base64_encode(random_bytes(12)),
            'notes' => null,
        ];
    }
}
