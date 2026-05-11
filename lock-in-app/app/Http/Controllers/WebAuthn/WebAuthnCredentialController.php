<?php

namespace App\Http\Controllers\WebAuthn;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Laragear\WebAuthn\Models\WebAuthnCredential;

use function response;

class WebAuthnCredentialController
{
    public function index(Request $request): JsonResponse
    {
        $credentials = $request->user()
            ->webAuthnCredentials()
            ->whereNull('disabled_at')
            ->orderByDesc('created_at')
            ->get(['id', 'alias', 'aaguid', 'created_at'])
            ->map(fn (WebAuthnCredential $c) => [
                'id' => $c->id,
                'alias' => $c->alias ?? 'Passkey',
                'created_at' => $c->created_at?->toDateString(),
            ]);

        return response()->json($credentials);
    }

    public function destroy(Request $request, string $id): Response
    {
        $request->user()
            ->webAuthnCredentials()
            ->whereKey($id)
            ->delete();

        return response()->noContent();
    }
}
