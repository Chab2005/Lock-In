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

    public function test_update_vault_entry(): void
    {
        $user = User::factory()->create();
        $entry = VaultEntry::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user);

        $response = $this->putJson("/vault/entries/{$entry->id}", [
            'nickname' => 'Updated Nickname',
            'website' => 'https://updated.example.com',
            'email_hint' => 'new@example.com',
            'notes' => 'Some private note.',
        ]);

        $response->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseHas('vault_entries', [
            'id' => $entry->id,
            'nickname' => 'Updated Nickname',
            'website' => 'https://updated.example.com',
            'email_hint' => 'new@example.com',
            'notes' => 'Some private note.',
        ]);
    }

    public function test_update_vault_entry_with_new_password(): void
    {
        $user = User::factory()->create();
        $entry = VaultEntry::factory()->create(['user_id' => $user->id]);
        $originalIv = $entry->iv;

        $this->actingAs($user);

        $response = $this->putJson("/vault/entries/{$entry->id}", [
            'nickname' => 'Same',
            'encrypted_password' => base64_encode('new-ciphertext'),
            'iv' => base64_encode('newivbytes'),
        ]);

        $response->assertOk()->assertJson(['success' => true]);

        $entry->refresh();
        $this->assertNotEquals($originalIv, $entry->iv);
        $this->assertEquals(base64_encode('newivbytes'), $entry->iv);
    }

    public function test_update_without_password_keeps_existing_ciphertext(): void
    {
        $user = User::factory()->create();
        $entry = VaultEntry::factory()->create(['user_id' => $user->id]);
        $originalPassword = $entry->encrypted_password;
        $originalIv = $entry->iv;

        $this->actingAs($user);

        // Send only one of the two required fields — server must keep existing ciphertext
        $response = $this->putJson("/vault/entries/{$entry->id}", [
            'nickname' => 'New Name',
            'encrypted_password' => base64_encode('partial-update'),
            // 'iv' intentionally omitted
        ]);

        $response->assertOk();

        $entry->refresh();
        $this->assertEquals($originalPassword, $entry->encrypted_password);
        $this->assertEquals($originalIv, $entry->iv);
    }

    public function test_cannot_update_another_users_entry(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();
        $entry = VaultEntry::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($attacker);

        $response = $this->putJson("/vault/entries/{$entry->id}", [
            'nickname' => 'Hacked',
        ]);

        $response->assertForbidden();

        $this->assertDatabaseMissing('vault_entries', [
            'id' => $entry->id,
            'nickname' => 'Hacked',
        ]);
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
