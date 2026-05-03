<?php

/**
 * ADVANCED EXAMPLE: Passing Data to Blade Views
 *
 * Demonstrates how to pass variables to views while using the layout system.
 * Add these examples to routes/web.php
 */

use Illuminate\Support\Facades\Route;

// Example 1: Simple data passing
Route::get('/user/{id}', function ($id) {
    return view('pages.user-profile', [
        'userId' => $id,
        'userName' => 'John Doe',  // Will be available in the view as $userName
    ]);
});

// Example 2: Using Route Model Binding
// Route::get('/post/{post}', function (Post $post) {
//     return view('pages.post-detail', compact('post'));
// });

// Example 3: Passing collections
Route::get('/dashboard', function () {
    $tasks = [
        ['id' => 1, 'title' => 'Complete Laravel tutorial', 'completed' => true],
        ['id' => 2, 'title' => 'Build Blade layout system', 'completed' => true],
        ['id' => 3, 'title' => 'Deploy to production', 'completed' => false],
    ];

    return view('pages.dashboard', compact('tasks'));
});

// Example 4: Using Controller class
// Route::get('/products', 'ProductController@index');
// Then in ProductController:
// public function index() {
//     $products = Product::all();
//     return view('pages.products', compact('products'));
// }
