<?php

namespace Tests\Feature;

use App\Mail\PasswordShared;
use App\Models\SharedEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ShareTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'recipient_email' => 'recipient@example.com',
            'label' => 'GitHub',
            'encrypted_payload' => base64_encode('ciphertext'),
            'iv' => base64_encode('123456789012'),
            'share_token_hash' => str_repeat('a', 64),
        ], $overrides);
    }

    public function test_store_creates_share_and_returns_share_id(): void
    {
        Mail::fake();

        $owner = User::factory()->create();
        $this->actingAs($owner);

        $response = $this->postJson('/share/entries', $this->validPayload());

        $response->assertCreated()
            ->assertJsonStructure(['success', 'share_id', 'recipient_exists']);

        $this->assertDatabaseHas('shared_entries', [
            'recipient_email' => 'recipient@example.com',
            'label' => 'GitHub',
            'status' => 'active',
        ]);
    }

    public function test_store_queues_email_when_share_key_is_provided(): void
    {
        Mail::fake();

        $owner = User::factory()->create();
        $this->actingAs($owner);

        $shareKey = base64_encode(random_bytes(32));

        $response = $this->postJson('/share/entries', $this->validPayload([
            'share_key' => $shareKey,
        ]));

        $response->assertCreated();

        Mail::assertQueued(PasswordShared::class, function (PasswordShared $mail) {
            return $mail->hasTo('recipient@example.com');
        });
    }

    public function test_store_does_not_queue_email_when_share_key_is_absent(): void
    {
        Mail::fake();

        $owner = User::factory()->create();
        $this->actingAs($owner);

        $this->postJson('/share/entries', $this->validPayload())->assertCreated();

        Mail::assertNothingQueued();
    }

    public function test_share_url_in_email_contains_claim_path_and_key_fragment(): void
    {
        Mail::fake();

        $owner = User::factory()->create();
        $this->actingAs($owner);

        $shareKey = base64_encode(random_bytes(32));

        $response = $this->postJson('/share/entries', $this->validPayload([
            'share_key' => $shareKey,
        ]));

        $shareId = $response->json('share_id');

        Mail::assertQueued(PasswordShared::class, function (PasswordShared $mail) use ($shareId) {
            return str_contains($mail->shareUrl, "/share/claim/{$shareId}")
                && str_contains($mail->shareUrl, '#key=');
        });
    }

    public function test_cannot_share_with_yourself(): void
    {
        Mail::fake();

        $owner = User::factory()->create(['email' => 'self@example.com']);
        $this->actingAs($owner);

        $response = $this->postJson('/share/entries', $this->validPayload([
            'recipient_email' => 'self@example.com',
        ]));

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['recipient_email']);

        Mail::assertNothingQueued();
    }

    public function test_cannot_create_share_unauthenticated(): void
    {
        Mail::fake();

        $this->postJson('/share/entries', $this->validPayload())->assertUnauthorized();

        Mail::assertNothingQueued();
    }

    public function test_recipient_exists_flag_is_true_when_recipient_has_account(): void
    {
        Mail::fake();

        $owner = User::factory()->create();
        User::factory()->create(['email' => 'known@example.com']);
        $this->actingAs($owner);

        $response = $this->postJson('/share/entries', $this->validPayload([
            'recipient_email' => 'known@example.com',
        ]));

        $response->assertCreated()->assertJson(['recipient_exists' => true]);
    }

    public function test_recipient_exists_flag_is_false_when_recipient_has_no_account(): void
    {
        Mail::fake();

        $owner = User::factory()->create();
        $this->actingAs($owner);

        $response = $this->postJson('/share/entries', $this->validPayload([
            'recipient_email' => 'unknown@example.com',
        ]));

        $response->assertCreated()->assertJson(['recipient_exists' => false]);
    }

    public function test_revoke_marks_share_as_revoked(): void
    {
        $owner = User::factory()->create();
        $share = SharedEntry::factory()->create(['owner_id' => $owner->id]);
        $this->actingAs($owner);

        $this->deleteJson("/share/entries/{$share->id}")->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseHas('shared_entries', ['id' => $share->id, 'status' => 'revoked']);
    }

    public function test_non_owner_cannot_revoke_share(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();
        $share = SharedEntry::factory()->create(['owner_id' => $owner->id]);
        $this->actingAs($attacker);

        $this->deleteJson("/share/entries/{$share->id}")->assertForbidden();

        $this->assertDatabaseHas('shared_entries', ['id' => $share->id, 'status' => 'active']);
    }
}
