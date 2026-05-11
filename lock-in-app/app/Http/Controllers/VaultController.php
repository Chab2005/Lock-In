<?php

namespace App\Http\Controllers;

use App\Models\VaultEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class VaultController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $entries = VaultEntry::where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->get(['id', 'website', 'nickname', 'email_hint', 'icon', 'encrypted_password', 'iv', 'notes', 'created_at']);

        return response()->json($entries);
    }

    public function getSalt(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->vault_salt) {
            $user->vault_salt = bin2hex(random_bytes(32));
            $user->vault_kdf_params = json_encode([
                'iterations' => 310000,
                'hash' => 'SHA-256',
                'keyLen' => 256,
            ]);
            $user->save();
        }

        return response()->json([
            'salt' => $user->vault_salt,
            'params' => json_decode($user->vault_kdf_params, true),
            'has_verifier' => $user->vault_verifier !== null,
        ]);
    }

    public function getVerifier(Request $request): JsonResponse
    {
        return response()->json([
            'verifier' => $request->user()->vault_verifier,
        ]);
    }

    public function setVerifier(Request $request): JsonResponse
    {
        $request->validate([
            'verifier' => 'required|string|max:1024',
        ]);

        $request->user()->update(['vault_verifier' => $request->verifier]);

        return response()->json(['success' => true]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'website' => 'nullable|string|max:255',
            'nickname' => 'nullable|string|max:255',
            'email_hint' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:2000',
            'icon' => 'nullable|string|max:64',
            'encrypted_password' => 'required|string',
            'iv' => 'required|string|max:32',
        ]);

        $entry = VaultEntry::create([
            'user_id' => $request->user()->id,
            'website' => $request->website,
            'nickname' => $request->nickname,
            'email_hint' => $request->email_hint,
            'notes' => $request->notes,
            'icon' => $request->icon ?? 'lock',
            'encrypted_password' => $request->encrypted_password,
            'iv' => $request->iv,
        ]);

        return response()->json(['success' => true, 'id' => $entry->id], 201);
    }

    public function update(Request $request, VaultEntry $entry): JsonResponse
    {
        Gate::authorize('update', $entry);

        $validated = $request->validate([
            'website' => 'nullable|string|max:255',
            'nickname' => 'nullable|string|max:255',
            'email_hint' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:2000',
            'encrypted_password' => 'nullable|string',
            'iv' => 'nullable|string|max:32',
        ]);

        // Only swap ciphertext when both fields arrive together; never leave them out of sync.
        if (! (isset($validated['encrypted_password']) && isset($validated['iv']))) {
            unset($validated['encrypted_password'], $validated['iv']);
        }

        $entry->update($validated);

        return response()->json([
            'success' => true,
            'entry' => [
                'id' => $entry->id,
                'nickname' => $entry->nickname,
                'website' => $entry->website,
                'email_hint' => $entry->email_hint,
                'notes' => $entry->notes,
                'icon' => $entry->icon,
            ],
        ]);
    }

    public function destroy(Request $request, VaultEntry $entry): JsonResponse
    {
        Gate::authorize('delete', $entry);

        $entry->delete();

        return response()->json(['success' => true]);
    }
}
