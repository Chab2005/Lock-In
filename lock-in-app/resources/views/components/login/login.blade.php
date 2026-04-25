<!-- login/index.html -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LockIn - Login</title>
    <!-- Google Fonts: Space Grotesk -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Stylesheets -->
    @vite(['resources/css/root.css', 'resources/css/login.css'])
    <link rel="stylesheet" href="{{ asset('css/root.css') }}">
    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
</head>
<body class="login-page">
<main class="auth-container">
    <header class="auth-header">
        <div class="logo-icon">🔑</div>
        <h1 class="brand-name">LOCKIN VAULT</h1>
        <p class="tagline">ARCHITECTURAL SECURITY</p>
    </header>

    <section class="auth-card">
        <span class="badge">IDENTITY REFERENCE</span>
        <form>
            <div class="form-group">
                <label for="email">EMAIL ADDRESS</label>
                <input type="email" id="email" placeholder="admin@vault.internal" required>
            </div>
            <div class="form-group">
                <label for="password">MASTER KEYCODE</label>
                <input type="password" id="password" placeholder="••••••••••••" required>
            </div>
            <button type="submit" class="btn-primary">
                UNLOCK ACCESS <span>→</span>
            </button>
        </form>

        <div class="divider">
            <span>OR</span>
        </div>

        <div class="social-auth">
            <button class="btn-outline">BIOMETRIC</button>
            <button class="btn-outline">SECURITY KEY</button>
        </div>

        <footer class="auth-footer">
            <a href="#">CREATE ACCOUNT</a>
            <span style="margin: 0 1rem; opacity: 0.2;">|</span>
            <a href="#">FORGOT PASSWORD?</a>
        </footer>
    </section>
</main>

<footer class="global-footer">
    <div class="footer-logo">LOCKIN</div>
    <nav class="footer-links">
        <a href="#">SECURITY PROTOCOL</a>
        <a href="#">API ACCESS</a>
        <a href="#">SUPPORT</a>
    </nav>
    <p class="copyright">© 2024 LOCKIN DIGITAL VAULT. ARCHITECTURAL SECURITY.</p>
</footer>
</body>
</html>
