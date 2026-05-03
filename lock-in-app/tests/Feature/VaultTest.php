<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VaultEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VaultTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_salt_generates_salt_for_new_user(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->getJson('/vault/salt');

        $response->assertOk()
            ->assertJsonStructure(['salt', 'params', 'has_verifier']);

        $this->assertNotNull($response->json('salt'));
        $this->assertFalse($response->json('has_verifier'));
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
        ]);

        $user->refresh();
        $this->assertNotNull($user->vault_salt);
    }

    public function test_get_salt_returns_existing_salt(): void
    {
        $user = User::factory()->create([
            'vault_salt' => 'existingsalt1234',
            'vault_kdf_params' => json_encode(['iterations' => 310000, 'hash' => 'SHA-256', 'keyLen' => 256]),
        ]);
        $this->actingAs($user);

        $response = $this->getJson('/vault/salt');

        $response->assertOk()
            ->assertJsonFragment(['salt' => 'existingsalt1234']);
    }

    public function test_set_and_get_verifier(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $verifier = 'encrypteddata.ivvalue';

        $setResponse = $this->postJson('/vault/verifier', ['verifier' => $verifier]);
        $setResponse->assertOk()->assertJson(['success' => true]);

        $getResponse = $this->getJson('/vault/verifier');
        $getResponse->assertOk()->assertJsonFragment(['verifier' => $verifier]);
    }

    public function test_store_vault_entry(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $payload = [
            'website' => 'https://github.com',
            'nickname' => 'GitHub',
            'email_hint' => 'user@example.com',
            'encrypted_password' => base64_encode('encrypteddata'),
            'iv' => base64_encode('ivbytes123'),
        ];

        $response = $this->postJson('/vault/entries', $payload);

        $response->assertCreated()
            ->assertJson(['success' => true])
            ->assertJsonStructure(['id']);

        $this->assertDatabaseHas('vault_entries', [
            'user_id' => $user->id,
            'website' => 'https://github.com',
            'nickname' => 'GitHub',
            'email_hint' => 'user@example.com',
        ]);
    }

    public function test_cannot_store_entry_without_encrypted_password(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->postJson('/vault/entries', [
            'website' => 'https://example.com',
            'iv' => base64_encode('ivbytes123'),
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['encrypted_password']);
    }

    public function test_cannot_access_vault_unauthenticated(): void
    {
        $response = $this->getJson('/vault/salt');

        $response->assertUnauthorized();
    }

    public function test_delete_vault_entry(): void
    {
        $user = User::factory()->create();
        $entry = VaultEntry::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user);

        $response = $this->deleteJson("/vault/entries/{$entry->id}");

        $response->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseMissing('vault_entries', ['id' => $entry->id]);
    }

    public function test_cannot_delete_another_users_entry(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();
        $entry = VaultEntry::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($attacker);

        $response = $this->deleteJson("/vault/entries/{$entry->id}");

        $response->assertForbidden();
        $this->assertDatabaseHas('vault_entries', ['id' => $entry->id]);
    }

    public function test_dashboard_passes_entries_to_view(): void
    {
        $user = User::factory()->create();
        VaultEntry::factory()->count(3)->create(['user_id' => $user->id]);

        $this->actingAs($user);

        $response = $this->get(route('dashboard'));

        $response->assertOk()
            ->assertViewHas('entries');

        $entries = $response->viewData('entries');
        $this->assertCount(3, $entries);
    }
}
