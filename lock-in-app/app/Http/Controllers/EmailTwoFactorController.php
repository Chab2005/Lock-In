<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class EmailTwoFactorController extends Controller
{
    // GET /2fa/email — show OTP challenge page
    public function show(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('login.id')) {
            return redirect()->route('login');
        }

        $user = User::find($request->session()->get('login.id'));
        if (! $user) {
            return redirect()->route('login');
        }

        return view('auth.email-otp-challenge', [
            'maskedEmail' => $this->maskEmail($user->email),
        ]);
    }

    // GET /2fa/email/otp — return OTP from session to the browser (consumed once)
    // The browser uses it to send the email via EmailJS, so the key never travels as a URL parameter
    public function getOtp(Request $request): JsonResponse
    {
        if (! $request->session()->has('login.id')) {
            return response()->json(['error' => 'Unauthorized.'], 403);
        }

        $user = User::find($request->session()->get('login.id'));
        if (! $user) {
            return response()->json(['error' => 'Unauthorized.'], 403);
        }

        $otp = $request->session()->pull('email_otp');

        if (! $otp) {
            return response()->json(['error' => 'No pending code. Click Resend to get a new one.'], 404);
        }

        return response()->json([
            'otp' => $otp,
            'email' => $user->email,
        ]);
    }

    // POST /2fa/email/resend — regenerate OTP and store in session
    public function resend(Request $request): JsonResponse
    {
        $userId = $request->session()->get('login.id');
        if (! $userId) {
            return response()->json(['error' => 'Unauthorized.'], 403);
        }

        $user = User::find($userId);
        if (! $user || ! $user->hasEmailTwoFactorEnabled()) {
            return response()->json(['error' => 'Unauthorized.'], 403);
        }

        // Prevent spam: reject if OTP was generated less than 60 seconds ago
        if ($user->email_otp_expires_at
            && $user->email_otp_expires_at->isFuture()
            && $user->email_otp_expires_at->diffInSeconds() > 240) {
            return response()->json(['error' => 'Wait a moment before requesting a new code.'], 429);
        }

        $otp = $this->generateOtp($user);
        $request->session()->put('email_otp', $otp);

        return response()->json(['success' => true]);
    }

    // POST /2fa/email/verify — verify code and complete login
    public function verify(Request $request): RedirectResponse
    {
        $userId = $request->session()->get('login.id');
        if (! $userId) {
            return redirect()->route('login');
        }

        $user = User::find($userId);
        if (! $user) {
            return redirect()->route('login');
        }

        $request->validate(['code' => ['required', 'string', 'size:6', 'regex:/^\d{6}$/']]);

        if (! $user->email_otp_hash
            || ! $user->email_otp_expires_at
            || now()->isAfter($user->email_otp_expires_at)
            || ! hash_equals($user->email_otp_hash, hash('sha256', $request->code))) {
            return back()->withErrors(['code' => 'Invalid or expired code. Request a new one.']);
        }

        $user->forceFill([
            'email_otp_hash' => null,
            'email_otp_expires_at' => null,
        ])->save();

        $remember = $request->session()->pull('login.remember', false);
        $request->session()->forget('login.id');
        Auth::loginUsingId($user->id, $remember);
        $request->session()->regenerate();

        return redirect()->intended('/dashboard');
    }

    // POST /2fa/email/toggle — enable or disable email OTP (requires full auth)
    public function toggle(Request $request): JsonResponse
    {
        $request->validate(['enabled' => 'required|boolean']);

        // Disabling email OTP is only allowed when the user has a confirmed TOTP
        // authenticator app — otherwise the account would have no MFA method at all.
        if (! $request->boolean('enabled') && ! $request->user()->hasTwoFactorEnabled()) {
            return response()->json([
                'message' => 'You must set up an authenticator app (TOTP) before disabling email OTP.',
            ], 422);
        }

        $request->user()->forceFill([
            'is_2fa_email_enabled' => $request->boolean('enabled'),
        ])->save();

        return response()->json([
            'success' => true,
            'enabled' => $request->boolean('enabled'),
        ]);
    }

    private function generateOtp(User $user): string
    {
        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $user->forceFill([
            'email_otp_hash' => hash('sha256', $otp),
            'email_otp_expires_at' => now()->addMinutes(5),
        ])->save();

        return $otp;
    }

    private function maskEmail(string $email): string
    {
        [$local, $domain] = explode('@', $email, 2);
        $visible = min(2, strlen($local));
        $masked = substr($local, 0, $visible).str_repeat('*', max(0, strlen($local) - $visible));

        return $masked.'@'.$domain;
    }
}
