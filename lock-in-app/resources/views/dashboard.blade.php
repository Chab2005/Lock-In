<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lock In - Dashboard</title>

    @vite(['resources/css/dashboard.css',
           'resources/css/header_logged.css',
           'resources/css/footer_logged.css'
    ])

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Space+Grotesk:wght@400;600;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />
</head>
    <body>
    <x-header_logged/>

    <main class="main">

        <x-dashboard.hero/>

        <x-dashboard.sectionHeader/>

        <section class="grid">
            <x-dashboard.cardPassword/>
            <x-dashboard.cardPassword/>
            <x-dashboard.cardPassword/>
            <x-dashboard.cardPassword/>

        </section>

    </main>

    <x-footer/>
    </body>
</html>
