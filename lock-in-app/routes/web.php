<?php

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

Route::get('/login', function() {return view('login');})->name('login');

Route::get('/register', function() {
    if (Auth::check()) {
        return view('register');
    } else {
        return redirect('/');
    }
})->name('register');

Route::get('/dashboard', function() {
    return view('dashboard');
})->name('dashboard');

Route::get('/generator', function() {return view('generator');})->name('generator');

Route::get('/setings', function() {return view('setings');})->name('setings');

require __DIR__.'/settings.php';
