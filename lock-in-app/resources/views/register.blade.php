<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription | LOCK IN</title>

    @vite(['resources/css/root.css', 'resources/css/login.css'])

    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />
</head>

<body class="login-page">
<div class="auth-container">

    <!-- HEADER -->
    <header class="auth-header">
        <h1 class="brand-name">LOCK IN</h1>
    </header>

    <!-- CARD -->
    <main class="auth-card">

        <div class="badge">INITIALISATION</div>

        <form class="auth-form" method="POST" action="{{ route('register') }}">
            @csrf

            <!-- EMAIL -->
            <div class="form-group">
                <label for="email">MAIL</label>
                <input type="email" name="email" id="email" placeholder="votre@email.com" required>
            </div>

            <!-- PASSWORD -->
            <div class="form-group">
                <label for="password">MOT DE PASSE</label>
                <input type="password" name="password" id="password" placeholder="••••••••••••" required>
            </div>

            <!-- CONFIRM PASSWORD -->
            <div class="form-group">
                <label for="password_confirmation">CONFIRMATION</label>
                <input type="password" name="password_confirmation" id="password_confirmation" placeholder="••••••••••••" required>
            </div>

            <!-- SUBMIT -->
            <button type="submit" class="btn-primary">
                CRÉER UN COMPTE
                <span class="material-symbols-outlined">arrow_forward</span>
            </button>
        </form>



    </main>

    <!-- FOOTER -->
    <footer class="auth-footer">
        <p>Déjà inscrit ? <a href="{{ route('login') }}">Se connecter.</a></p>
    </footer>

</div>
</body>
</html>
