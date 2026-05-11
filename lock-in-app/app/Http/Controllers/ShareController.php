<?php

namespace App\Http\Controllers;

use App\Mail\PasswordShared;
use App\Models\ShareAuditLog;
use App\Models\SharedEntry;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class ShareController extends Controller
{
    // POST /share/entries
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'recipient_email' => [
                'required', 'email', 'max:255',
                Rule::notIn([strtolower($user->email)]),
            ],
            'label' => 'nullable|string|max:255',
            'encrypted_payload' => 'required|string',
            'iv' => 'required|string|max:24',
            'share_token_hash' => 'required|string|size:64',
            // base64-encoded 32-byte AES key (44 chars); sent over TLS, never persisted
            'share_key' => 'nullable|string|max:64',
        ], [
            'recipient_email.not_in' => 'You cannot share an entry with yourself.',
        ]);

        $recipient = User::where('email', strtolower($request->recipient_email))->first();

        $share = SharedEntry::create([
            'owner_id' => $user->id,
            'recipient_id' => $recipient?->id,
            'recipient_email' => strtolower($request->recipient_email),
            'label' => $request->label,
            'encrypted_payload' => $request->encrypted_payload,
            'iv' => $request->iv,
            'share_token_hash' => $request->share_token_hash,
            'status' => 'active',
        ]);

        ShareAuditLog::create([
            'shared_entry_id' => $share->id,
            'actor_id' => $user->id,
            'event' => 'created',
            'ip_address' => $request->ip(),
            'user_agent' => substr($request->userAgent() ?? '', 0, 512),
        ]);

        if ($request->filled('share_key')) {
            $shareUrl = url("/share/claim/{$share->id}").'#key='.urlencode($request->share_key);

            try {
                Mail::to($share->recipient_email)->queue(new PasswordShared($share, $shareUrl));
            } catch (Throwable $e) {
                logger()->error('share_email_failed', [
                    'share_id' => $share->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'share_id' => $share->id,
            'recipient_exists' => $recipient !== null,
        ], 201);
    }

    // GET /share/entries — shares I sent (active)
    public function myShares(Request $request): JsonResponse
    {
        $shares = SharedEntry::where('owner_id', $request->user()->id)
            ->where('status', 'active')
            ->orderByDesc('created_at')
            ->get(['id', 'recipient_email', 'label', 'created_at']);

        return response()->json(['shares' => $shares]);
    }

    // GET /share/claim/{share} — page for recipient to decrypt a shared entry
    public function claim(Request $request, SharedEntry $share): View
    {
        $user = $request->user();

        abort_if($share->isRevoked(), 404, 'This share has been revoked.');
        abort_if(strtolower($share->recipient_email) !== strtolower($user->email), 403);

        ShareAuditLog::create([
            'shared_entry_id' => $share->id,
            'actor_id' => $user->id,
            'event' => 'viewed',
            'ip_address' => $request->ip(),
            'user_agent' => substr($request->userAgent() ?? '', 0, 512),
        ]);

        return view('share.claim', ['share' => $share]);
    }

    // GET /share/fetch/{share} — encrypted payload (JSON) for client-side decryption
    public function fetchPayload(Request $request, SharedEntry $share): JsonResponse
    {
        $user = $request->user();

        if ($share->isRevoked()) {
            return response()->json(['error' => 'Share has been revoked.'], 404);
        }

        abort_if(strtolower($share->recipient_email) !== strtolower($user->email), 403);

        return response()->json([
            'encrypted_payload' => $share->encrypted_payload,
            'iv' => $share->iv,
            'label' => $share->label,
            'owner_name' => $share->owner->name,
        ]);
    }

    // POST /share/accept/{share} — recipient accepts and marks share as claimed
    public function accept(Request $request, SharedEntry $share): JsonResponse
    {
        $user = $request->user();

        if ($share->isRevoked()) {
            return response()->json(['error' => 'Share has been revoked.'], 404);
        }

        if ($share->status === 'claimed') {
            return response()->json(['error' => 'Share already claimed.'], 409);
        }

        abort_if(strtolower($share->recipient_email) !== strtolower($user->email), 403);

        $share->update(['status' => 'claimed']);

        ShareAuditLog::create([
            'shared_entry_id' => $share->id,
            'actor_id' => $user->id,
            'event' => 'claimed',
            'ip_address' => $request->ip(),
            'user_agent' => substr($request->userAgent() ?? '', 0, 512),
        ]);

        return response()->json(['success' => true]);
    }

    // DELETE /share/entries/{share} — owner revokes a share
    public function revoke(Request $request, SharedEntry $share): JsonResponse
    {
        abort_if($share->owner_id !== $request->user()->id, 403);

        $share->update(['status' => 'revoked']);

        ShareAuditLog::create([
            'shared_entry_id' => $share->id,
            'actor_id' => $request->user()->id,
            'event' => 'revoked',
            'ip_address' => $request->ip(),
            'user_agent' => substr($request->userAgent() ?? '', 0, 512),
        ]);

        return response()->json(['success' => true]);
    }
}
