<?php

namespace App\Http\Controllers;

use App\Models\VaultEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VaultController extends Controller
{
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
            'icon' => 'nullable|string|max:64',
            'encrypted_password' => 'required|string',
            'iv' => 'required|string|max:32',
        ]);

        $entry = VaultEntry::create([
            'user_id' => $request->user()->id,
            'website' => $request->website,
            'nickname' => $request->nickname,
            'email_hint' => $request->email_hint,
            'icon' => $request->icon ?? 'lock',
            'encrypted_password' => $request->encrypted_password,
            'iv' => $request->iv,
        ]);

        return response()->json(['success' => true, 'id' => $entry->id], 201);
    }

    public function destroy(Request $request, VaultEntry $entry): JsonResponse
    {
        if ($entry->user_id !== $request->user()->id) {
            abort(403);
        }

        $entry->delete();

        return response()->json(['success' => true]);
    }
}
