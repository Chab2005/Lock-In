<main class="auth-card">
    <div class="badge">AUTH REQUIRED</div>

    <form class="auth-form" method="POST" action="{{ route('login') }}">
        @csrf

        <div class="form-group">
            <label for="email">EMAIL</label>
            <input type="email" name="email" id="email" placeholder="your@email.com" required>
        </div>

        <div class="form-group">
            <label for="password">PASSWORD</label>
            <input type="password" name="password" id="password" placeholder="••••••••••••" required>
        </div>

        <button type="submit" class="btn-primary">
            SIGN IN
            <span class="material-symbols-outlined">arrow_forward</span>
        </button>
    </form>

    <div class="divider">
        <span>ALTERNATIVE METHODS</span>
    </div>

    <div class="social-auth">
        <button class="btn-outline">
            <span class="material-symbols-outlined">key</span> Security Key
        </button>
    </div>
</main>
