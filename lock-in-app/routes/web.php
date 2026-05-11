<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\EmailTwoFactorController;
use App\Http\Controllers\ExtensionAuthController;
use App\Http\Controllers\PasswordGeneratorController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\ShareController;
use App\Http\Controllers\VaultController;
use App\Http\Controllers\WebAuthn\WebAuthnCredentialController;
use App\Http\Controllers\WebAuthn\WebAuthnLoginController;
use App\Http\Controllers\WebAuthn\WebAuthnRegisterController;
use App\Http\Middleware\AuthenticateExtensionToken;
use App\Models\SharedEntry;
use App\Models\VaultEntry;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (Auth::check()) {
        return redirect('/dashboard');
    } else {
        return view('/login');
    }
})->name('/');

Route::get('/login', function () {
    return view('login');
})->middleware('guest')->name('login');

Route::get('/register', function () {
    return view('register');
})->middleware('guest')->name('register');

Route::post('/login', [AuthController::class, 'login']);

Route::post('/register', [AuthController::class, 'register']);

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::get('/dashboard', function () {
    $entries = VaultEntry::where('user_id', auth()->id())->orderByDesc('created_at')->get();

    $receivedShares = SharedEntry::where('recipient_email', strtolower(auth()->user()->email))
        ->where('status', 'active')
        ->with('owner:id,first_name,last_name')
        ->orderByDesc('created_at')
        ->get();

    return view('dashboard', compact('entries', 'receivedShares'));
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified', 'throttle:60,1'])->prefix('vault')->group(function () {
    Route::get('/entries', [VaultController::class, 'index']);
    Route::get('/salt', [VaultController::class, 'getSalt']);
    Route::get('/verifier', [VaultController::class, 'getVerifier']);
    Route::post('/verifier', [VaultController::class, 'setVerifier']);
    Route::post('/entries', [VaultController::class, 'store']);
    Route::put('/entries/{entry}', [VaultController::class, 'update']);
    Route::delete('/entries/{entry}', [VaultController::class, 'destroy']);
});

// ── Browser extension API (token auth, no CSRF, no session) ──────────────────
Route::prefix('ext')->middleware(['throttle:60,1'])->group(function () {
    Route::post('/login', [ExtensionAuthController::class, 'login']);
    Route::post('/logout', [ExtensionAuthController::class, 'logout'])->middleware(AuthenticateExtensionToken::class);
    Route::get('/check', [ExtensionAuthController::class, 'check'])->middleware(AuthenticateExtensionToken::class);

    Route::middleware([AuthenticateExtensionToken::class])->prefix('vault')->group(function () {
        Route::get('/entries', [VaultController::class, 'index']);
        Route::get('/salt', [VaultController::class, 'getSalt']);
        Route::get('/verifier', [VaultController::class, 'getVerifier']);
        Route::post('/verifier', [VaultController::class, 'setVerifier']);
        Route::post('/entries', [VaultController::class, 'store']);
        Route::put('/entries/{entry}', [VaultController::class, 'update']);
        Route::delete('/entries/{entry}', [VaultController::class, 'destroy']);
    });
});

Route::get('/generator', function () {
    return view('generator');
})->middleware(['auth', 'verified'])->name('generator');

Route::get('/setings', function () {
    return view('setings');
})->middleware(['auth', 'verified'])->name('setings');

Route::post('/setings/password', [PasswordController::class, 'update'])
    ->middleware(['auth', 'verified', 'throttle:6,1'])
    ->name('setings.password.update');

Route::post('/api/generate-password', [PasswordGeneratorController::class, 'generate'])->middleware(['auth', 'verified']);

// Email OTP 2FA — accessible before full authentication (login.id in session)
Route::middleware('throttle:10,1')->group(function () {
    Route::get('/2fa/email', [EmailTwoFactorController::class, 'show'])->name('2fa.email.challenge');
    Route::get('/2fa/email/otp', [EmailTwoFactorController::class, 'getOtp']);
    Route::post('/2fa/email/verify', [EmailTwoFactorController::class, 'verify'])->name('2fa.email.verify');
    Route::post('/2fa/email/resend', [EmailTwoFactorController::class, 'resend']);
});

// Email OTP toggle — requires full authentication
Route::middleware(['auth', 'verified'])->post('/2fa/email/toggle', [EmailTwoFactorController::class, 'toggle']);

Route::middleware(['auth', 'verified', 'throttle:30,1'])->prefix('share')->group(function () {
    Route::post('/entries', [ShareController::class, 'store']);
    Route::get('/entries', [ShareController::class, 'myShares']);
    Route::get('/claim/{share}', [ShareController::class, 'claim'])->name('share.claim');
    Route::get('/fetch/{share}', [ShareController::class, 'fetchPayload']);
    Route::delete('/entries/{share}', [ShareController::class, 'revoke']);
    Route::post('/accept/{share}', [ShareController::class, 'accept'])->name('share.accept');
});

// WebAuthn — passkey registration/management (authenticated)
Route::middleware(['auth', 'verified', 'throttle:20,1'])->prefix('webauthn')->group(function () {
    Route::post('/register/options', [WebAuthnRegisterController::class, 'options'])->name('webauthn.register.options');
    Route::post('/register', [WebAuthnRegisterController::class, 'register'])->name('webauthn.register');
    Route::get('/credentials', [WebAuthnCredentialController::class, 'index'])->name('webauthn.credentials');
    Route::delete('/credentials/{id}', [WebAuthnCredentialController::class, 'destroy'])->name('webauthn.credentials.destroy');
});

// WebAuthn — passkey login (unauthenticated, before full session)
Route::middleware('throttle:10,1')->prefix('webauthn')->group(function () {
    Route::post('/login/options', [WebAuthnLoginController::class, 'options'])->name('webauthn.login.options');
    Route::post('/login', [WebAuthnLoginController::class, 'login'])->name('webauthn.login');
});

// Session management
Route::middleware(['auth', 'verified'])->prefix('sessions')->group(function () {
    Route::get('/', [SessionController::class, 'index'])->name('sessions.index');
    Route::delete('/{id}', [SessionController::class, 'destroy'])->name('sessions.destroy');
    Route::post('/logout-others', [SessionController::class, 'logoutOthers'])->name('sessions.logout-others');
});

require __DIR__.'/settings.php';
