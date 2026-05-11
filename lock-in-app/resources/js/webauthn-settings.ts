import webpass from '@laragear/webpass';

const wp = webpass.create({
    routes: {
        attestOptions: '/webauthn/register/options',
        attest:        '/webauthn/register',
        assertOptions: '/webauthn/login/options',
        assert:        '/webauthn/login',
    },
});

function esc(s: unknown): string {
    return String(s).replace(/[&<>"']/g, (c: string) =>
        ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' } as Record<string, string>)[c],
    );
}

function csrf(): string {
    return (document.querySelector('meta[name=csrf-token]') as HTMLMetaElement | null)?.content ?? '';
}

const Passkeys = {
    async load(): Promise<void> {
        const list = document.getElementById('passkeyList');
        if (!list) return;

        const res = await fetch('/webauthn/credentials', {
            headers: { Accept: 'application/json' },
        });

        if (!res.ok) {
            list.innerHTML = '<div class="card-item" style="font-size:13px;color:var(--color-text-secondary)">Could not load passkeys.</div>';
            return;
        }

        const data: { id: string; alias: string; created_at: string }[] = await res.json();

        if (!data.length) {
            list.innerHTML = '<div class="card-item" style="font-size:13px;color:var(--color-text-secondary)">No passkeys registered yet.</div>';
            return;
        }

        list.innerHTML = data
            .map(
                (c) => `
            <div class="card-item" data-id="${esc(c.id)}">
                <div class="item-info">
                    <div class="item-icon"><span class="material-symbols-outlined">fingerprint</span></div>
                    <div class="item-text">
                        <h4>${esc(c.alias)}</h4>
                        <p>Added ${esc(c.created_at)}</p>
                    </div>
                </div>
                <button class="btn-icon" onclick="Passkeys.remove('${esc(c.id)}')" title="Remove passkey">
                    <span class="material-symbols-outlined">delete</span>
                </button>
            </div>`,
            )
            .join('');
    },

    register(): void {
        const aliasInput = document.getElementById('passkeyAlias') as HTMLInputElement | null;
        const errEl = document.getElementById('passkeyNameError');
        if (aliasInput) aliasInput.value = '';
        if (errEl) errEl.style.display = 'none';
        window.ModalSystem?.open('passkey-name');
        setTimeout(() => aliasInput?.focus(), 100);
    },

    async confirmRegister(): Promise<void> {
        const aliasInput = document.getElementById('passkeyAlias') as HTMLInputElement | null;
        const errEl      = document.getElementById('passkeyNameError');
        const btn        = document.getElementById('addPasskeyBtn') as HTMLButtonElement | null;
        const passkeyErr = document.getElementById('passkeyError');
        const alias      = aliasInput?.value.trim() ?? '';

        if (!alias) {
            if (errEl) { errEl.textContent = 'Please enter a name.'; errEl.style.display = ''; }
            return;
        }

        if (errEl) errEl.style.display = 'none';
        window.ModalSystem?.close('passkey-name');
        if (btn) btn.disabled = true;
        if (passkeyErr) passkeyErr.style.display = 'none';

        try {
            const result = await wp.attest(
                { path: '/webauthn/register/options' },
                { path: '/webauthn/register', body: { alias } },
            );

            if (!result.success) throw result.error ?? new Error('Registration failed.');
            await this.load();
        } catch (err: unknown) {
            const msg = err instanceof Error ? err.message : 'Passkey registration failed. Try again.';
            if (passkeyErr) { passkeyErr.textContent = msg; passkeyErr.style.display = ''; }
        } finally {
            if (btn) btn.disabled = false;
        }
    },

    async remove(id: string): Promise<void> {
        if (!confirm('Remove this passkey? You will no longer be able to use it to sign in.')) return;

        const res = await fetch(`/webauthn/credentials/${encodeURIComponent(id)}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' },
        });

        const errEl = document.getElementById('passkeyError');
        if (res.ok) {
            await this.load();
        } else {
            if (errEl) { errEl.textContent = 'Could not remove passkey. Try again.'; errEl.style.display = ''; }
        }
    },
};

declare global {
    interface Window {
        Passkeys: typeof Passkeys;
        ModalSystem?: { open: (id: string) => void; close: (id: string) => void };
    }
}

window.Passkeys = Passkeys;

document.addEventListener('DOMContentLoaded', () => Passkeys.load());
