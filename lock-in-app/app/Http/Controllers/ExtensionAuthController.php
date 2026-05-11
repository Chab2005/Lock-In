<?php

namespace App\Http\Controllers;

use App\Models\ExtensionToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ExtensionAuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials)) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        $user = Auth::user();
        $raw = Str::random(64);

        ExtensionToken::create([
            'user_id' => $user->id,
            'token_hash' => ExtensionToken::hashToken($raw),
        ]);

        return response()->json(['token' => $raw]);
    }

    public function logout(Request $request): JsonResponse
    {
        $raw = $request->bearerToken();

        if ($raw) {
            ExtensionToken::where('token_hash', ExtensionToken::hashToken($raw))->delete();
        }

        return response()->json(['ok' => true]);
    }

    public function check(Request $request): JsonResponse
    {
        return response()->json(['ok' => true, 'user' => $request->user()?->email]);
    }
}
