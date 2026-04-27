<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Illuminate\Support\Facades\Auth;

Route::get('/', function() {
    if (Auth::check()) {
        return redirect('/dashboard');
    } else {
        return view('/login');
    }
})->name('/');

Route::get('/login', function() {return view('login');})->middleware('guest')->name('login');

Route::get('/register', function() {return view('register');})->middleware('guest')->name('register');

Route::post('/login', [AuthController::class, 'login']);

Route::post('/register', [AuthController::class, 'register']);

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::get('/dashboard', function() {
    return view('dashboard');
})->middleware('auth')->name('dashboard');

Route::get('/generator', function() {return view('generator');})->middleware('auth')->name('generator');

Route::get('/setings', function() {return view('setings');})->middleware('auth')->name('setings');

require __DIR__.'/settings.php';
