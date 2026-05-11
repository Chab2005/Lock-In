<?php

namespace App\Http\Middleware;

use App\Models\ExtensionToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateExtensionToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $raw = $request->bearerToken();

        if (! $raw) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $record = ExtensionToken::where('token_hash', ExtensionToken::hashToken($raw))
            ->with('user')
            ->first();

        if (! $record) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $record->update(['last_used_at' => now()]);

        $request->setUserResolver(fn () => $record->user);

        return $next($request);
    }
}
