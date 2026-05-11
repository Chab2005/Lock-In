<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $user = Auth::user();
            $request->session()->regenerate();

            // TOTP (authenticator app) takes priority over email OTP
            if ($user->hasTwoFactorEnabled()) {
                Auth::logout();
                $request->session()->put('login.id', $user->getKey());
                $request->session()->put('login.remember', $request->boolean('remember'));

                return redirect()->route('two-factor.login');
            }

            // Email OTP challenge
            if ($user->hasEmailTwoFactorEnabled()) {
                Auth::logout();
                $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                $user->forceFill([
                    'email_otp_hash' => hash('sha256', $otp),
                    'email_otp_expires_at' => now()->addMinutes(5),
                ])->save();
                $request->session()->put('login.id', $user->getKey());
                $request->session()->put('login.remember', $request->boolean('remember'));
                $request->session()->put('email_otp', $otp);

                return redirect()->route('2fa.email.challenge');
            }

            return redirect()->intended('/dashboard');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function register(Request $request)
    {
        $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'is_2fa_email_enabled' => true,
        ]);

        $user->sendEmailVerificationNotification();

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('verification.notice');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
