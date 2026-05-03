<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PasswordGeneratorController;
use App\Http\Controllers\VaultController;
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

    return view('dashboard', compact('entries'));
})->middleware('auth')->name('dashboard');

Route::middleware(['auth', 'throttle:60,1'])->prefix('vault')->group(function () {
    Route::get('/salt', [VaultController::class, 'getSalt']);
    Route::get('/verifier', [VaultController::class, 'getVerifier']);
    Route::post('/verifier', [VaultController::class, 'setVerifier']);
    Route::post('/entries', [VaultController::class, 'store']);
    Route::delete('/entries/{entry}', [VaultController::class, 'destroy']);
});

Route::get('/generator', function () {
    return view('generator');
})->middleware('auth')->name('generator');

Route::get('/setings', function () {
    return view('setings');
})->middleware('auth')->name('setings');

Route::post('/api/generate-password', [PasswordGeneratorController::class, 'generate'])->middleware('auth');

require __DIR__.'/settings.php';
