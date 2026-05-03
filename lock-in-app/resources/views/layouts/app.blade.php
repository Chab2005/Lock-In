<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title','Lock In')</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&amp;family=Space+Grotesk:wght@400;600;700;900&amp;display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@48,500,0,0&icon_names=lock" />
    @stack('styles')
    @vite([
        'resources/js/color-mode.js',
        'resources/css/root.css',
        'resources/css/vault.css',
        'resources/css/ui.css',
        'resources/js/modal.js',
        'resources/js/vault.js'
    ])
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Space+Grotesk:wght@400;500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />
</head>

<body>

{{-- Header --}}
@yield('header')

{{-- Page content --}}
<main class="main">
    @yield('content')
</main>

{{-- Footer --}}
@yield('footer')

<x-vault.unlock-modal/>
</body>
</html>
