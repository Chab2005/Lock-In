@php use Illuminate\Support\Facades\Route; @endphp
@php
    $route = Route::current()->uri();
    $userId = session('user_id');
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet"/>

        <!-- Styles / Scripts -->
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif

        <link rel="stylesheet" href="{{ asset("stylesheets/vendors/bootstrap-5.3.8-dist/bootstrap.min.css") }}">
        <link rel="stylesheet" href="{{ asset("stylesheets/components/layout.css") }}">
        <link rel="stylesheet" href="{{ asset("stylesheets/components/logo.css") }}">

        @stack("stylesheets")

        <script src="{{ asset("scripts/vendors/bootstrap-5.3.8-dist/bootstrap.bundle.min.js") }}"></script>

        @stack("scripts")
    </head>
    <body>
        <header>
            <!-- Header content will be added later -->
        </header>
        <main>
            {{ $slot }}
        </main>
        <footer>
            <!-- Footer content will be added later -->
        </footer>
    </body>
</html>
