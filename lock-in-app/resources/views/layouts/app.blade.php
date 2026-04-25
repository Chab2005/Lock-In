<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title','Lock In')</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&amp;family=Space+Grotesk:wght@400;600;700;900&amp;display=swap" rel="stylesheet">
    @stack('styles')
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



</body>
</html>
