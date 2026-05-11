import webpass from '@laragear/webpass';

const PasskeyLogin = {
    async login(): Promise<void> {
        const btn    = document.getElementById('passkeyLoginBtn') as HTMLButtonElement | null;
        const errEl  = document.getElementById('passkeyLoginError');

        if (btn)   btn.disabled = true;
        if (errEl) errEl.style.display = 'none';

        try {
            const result = await webpass.assert(
                { path: '/webauthn/login/options' },
                { path: '/webauthn/login' },
            );

            if (!result.success) throw result.error ?? new Error('Authentication failed.');

            // Successful — Fortify logs the user in, redirect to dashboard
            window.location.href = '/dashboard';
        } catch (err: unknown) {
            const msg = err instanceof Error ? err.message : 'Passkey sign-in failed. Try your password.';
            if (errEl) { errEl.textContent = msg; errEl.style.display = ''; }
        } finally {
            if (btn) btn.disabled = false;
        }
    },
};

declare global {
    interface Window {
        PasskeyLogin: typeof PasskeyLogin;
    }
}

window.PasskeyLogin = PasskeyLogin;
