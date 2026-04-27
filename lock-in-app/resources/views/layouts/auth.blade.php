<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title','Lock In')</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&amp;family=Space+Grotesk:wght@400;600;700;900&amp;display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@48,500,0,0&icon_names=lock" />
    @stack('styles')
    @vite(['resources/js/color-mode.js'])
</head>

<body class="login-page">
    <div class="auth-container">
        {{-- Header --}}
        @yield('header')

        {{-- Page content --}}
        @yield('content')

        {{-- Footer --}}
        @yield('footer')
    </div>
</body>
</html>

