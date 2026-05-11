<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @auth
    <meta name="user-name" content="{{ auth()->user()->name }}">
    <meta name="user-email" content="{{ auth()->user()->email }}">
    @endauth
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

{{-- EmailJS SDK --}}
<script src="https://cdn.jsdelivr.net/npm/@emailjs/browser@4/dist/email.min.js"></script>
<script>
    // ─── EmailJS Configuration (2FA OTP only) ────────────────────────────────
    window.EmailJSConfig = {
        PUBLIC_KEY:      'ba4kj6EA7k-sKfOWu',
        SERVICE_ID:      'service_h7ciewk',
        TEMPLATE_2FA_ID: 'template_2fa',
    };
    emailjs.init({ publicKey: window.EmailJSConfig.PUBLIC_KEY });
</script>

@stack('scripts')
</body>
</html>
