<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion | LOCK IN</title>
    <link rel="stylesheet" href="../css/root.css">
    <link rel="stylesheet" href="../css/login.css">
    @vite(['resources/css/root.css', 'resources/css/login.css'])
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
</head>
<body class="login-page">
    <div class="auth-container">
        <header class="auth-header">
            <h1 class="brand-name">LOCK IN</h1>
        </header>

        <main class="auth-card">
            <div class="badge">AUTH. REQUISE</div>

            <form class="auth-form">
                <div class="form-group">
                    <label for="email">MAIL</label>
                    <input type="email" id="email" placeholder="votre@email.com" required>
                </div>

                <div class="form-group">
                    <label for="password">MOT DE PASSE</label>
                    <input type="password" id="password" placeholder="••••••••••••" required>
                </div>

                <button type="submit" class="btn-primary">
                    <a href="{{ route('generator') }}"></a>
                    SE CONNECTER 
                    <span class="material-symbols-outlined">arrow_forward</span>
                </button>
            </form>

            <div class="divider">
                <span>MÉTHODES ALTERNATIVES</span>
            </div>

            <div class="social-auth">

                <button class="btn-outline">
                    <span class="material-symbols-outlined">key</span> Clé de sécurité
                </button>
            </div>
        </main>

        <footer class="auth-footer">
            <p>Pas encore inscrit ? <a href="{{ route('register') }}">Creer un compte.</a></p>
        </footer>
    </div>


</body>
</html>
