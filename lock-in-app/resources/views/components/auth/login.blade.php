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

        <div style="display: flex; justify-content: flex-end; margin-top: -8px; margin-bottom: 8px;">
            <a href="/forgot-password"
               style="font-size: 12px; color: var(--color-text-secondary);
                      text-decoration: underline; letter-spacing: 0.05em;">
                Forgot password?
            </a>
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
        <button type="button" class="btn-outline" id="passkeyLoginBtn"
                onclick="PasskeyLogin.login()">
            <span class="material-symbols-outlined">fingerprint</span> Sign in with Passkey
        </button>
    </div>

    <p id="passkeyLoginError"
       style="font-size:12px; color:#ff3d3d; text-align:center; margin-top:8px; display:none;"></p>
</main>

@push('scripts')
    @vite(['resources/js/webauthn-login.ts'])
@endpush
