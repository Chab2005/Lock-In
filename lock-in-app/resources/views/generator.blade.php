<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generator - VAULT</title>

    @vite([
        'resources/css/checkbox.css',
        'resources/css/addPassword.css',
        'resources/js/generator.js',
        'resources/css/header_logged.css',
        'resources/css/footer_logged.css'
    ])

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Space+Grotesk:wght@400;500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />
</head>
    <body>

        <x-header_logged/>

        <main class="main">
            <section class="hero">
                <h1>PASSWORD<br>GENERATOR</h1>
            </section>

            <x-generator.output/>

            <section class="grid">
                <div class="left">
                    <x-generator.entropyLevel/>
                    <x-generator.charSets/>
                    <x-generator.save/>
                </div>
                <x-generator.analysis/>
            </section>
        </main>

        <x-footer/>

    </body>
</html>
