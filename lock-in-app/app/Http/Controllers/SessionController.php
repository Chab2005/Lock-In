<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

use function response;

class SessionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $currentId = $request->session()->getId();

        $sessions = DB::table('sessions')
            ->where('user_id', $request->user()->getAuthIdentifier())
            ->orderByDesc('last_activity')
            ->get()
            ->map(fn (object $s) => [
                'id' => $s->id,
                'ip' => $s->ip_address ?? 'Unknown',
                'device' => $this->parseDevice($s->user_agent ?? ''),
                'last_activity' => $s->last_activity,
                'is_current' => $s->id === $currentId,
            ]);

        return response()->json($sessions);
    }

    public function destroy(Request $request, string $id): Response
    {
        if ($id === $request->session()->getId()) {
            abort(422, 'Cannot revoke current session. Use logout instead.');
        }

        DB::table('sessions')
            ->where('id', $id)
            ->where('user_id', $request->user()->getAuthIdentifier())
            ->delete();

        return response()->noContent();
    }

    public function logoutOthers(Request $request): Response
    {
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        if (! Hash::check($request->password, $request->user()->getAuthPassword())) {
            abort(422, 'The provided password is incorrect.');
        }

        Auth::logoutOtherDevices($request->password);

        return response()->noContent();
    }

    private function parseDevice(string $ua): string
    {
        if ($ua === '') {
            return 'Unknown device';
        }

        $device = match (true) {
            str_contains($ua, 'iPhone') => 'iPhone',
            str_contains($ua, 'iPad') => 'iPad',
            str_contains($ua, 'Android') => 'Android device',
            str_contains($ua, 'Macintosh') => 'Mac',
            str_contains($ua, 'Windows') => 'Windows PC',
            str_contains($ua, 'Linux') => 'Linux',
            default => 'Unknown device',
        };

        $browser = match (true) {
            str_contains($ua, 'Edg/') => 'Edge',
            str_contains($ua, 'Chrome/') => 'Chrome',
            str_contains($ua, 'Firefox/') => 'Firefox',
            str_contains($ua, 'Safari/') => 'Safari',
            default => '',
        };

        return $browser !== '' ? "{$device} · {$browser}" : $device;
    }
}
