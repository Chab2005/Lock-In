@extends('layouts.app')

@push('styles')
    @vite([
        'resources/css/header_logged.css',
        'resources/css/footer_logged.css',
    ])
@endpush

@section('title', 'Lock In - Shared Entry')

@section('header')
    <x-header_logged/>
@endsection

@section('content')
<div style="max-width: 640px; margin: 0 auto; padding-top: 40px;">

    <p style="font-size: 11px; letter-spacing: 0.2em; color: var(--color-text-secondary); margin-bottom: 24px;">
        SHARED WITH YOU
    </p>

    <h1 style="font-family: 'Space Grotesk'; font-size: 36px; margin-bottom: 8px;">
        {{ $share->label ?? 'Shared Entry' }}
    </h1>

    <p style="color: var(--color-text-secondary); font-size: 14px; margin-bottom: 40px;">
        Shared by <strong>{{ $share->owner->name }}</strong>
        on {{ $share->created_at->format('M j, Y') }}
    </p>

    <div style="background: var(--color-surface); border: 1px solid var(--color-border); padding: 32px;">

        {{-- Auto-decrypt state (shown when key comes from email link fragment) --}}
        <div id="shareAutoDecrypt" style="display: none; text-align: center; padding: 24px 0;">
            <span class="material-symbols-outlined"
                  style="font-size: 36px; color: var(--color-text-secondary); display: block; margin-bottom: 12px;">lock_open</span>
            <p style="font-size: 14px; color: var(--color-text-secondary);">Decrypting your shared entry…</p>
        </div>

        {{-- Manual decrypt prompt (fallback / error state) --}}
        <div id="shareDecryptPrompt">
            <p style="color: var(--color-text-secondary); font-size: 14px; margin-bottom: 20px; line-height: 1.6;">
                Enter the share code from the email that {{ $share->owner->name }} sent you.
            </p>

            <div style="margin-bottom: 16px;">
                <label style="font-size: 11px; color: var(--color-text-secondary);
                              letter-spacing: 0.1em; text-transform: uppercase; display: block; margin-bottom: 8px;">
                    SHARE CODE
                </label>
                <input type="text" id="shareCodeInput"
                       placeholder="Paste share code here…"
                       autocomplete="off"
                       style="width: 100%; background: transparent; border: none;
                              border-bottom: 1px solid var(--color-border); padding: 10px 0;
                              color: var(--color-text-primary); font-family: inherit; font-size: 14px;">
            </div>

            <p id="shareDecryptError"
               style="color: #ff3d3d; font-size: 13px; display: none; margin-bottom: 12px;"></p>

            <button type="button" id="decryptShareBtn" class="btn-primary" style="width: 100%;">
                DECRYPT <span class="material-symbols-outlined">lock_open</span>
            </button>
        </div>

        {{-- Step 2: decrypted — add to vault --}}
        <div id="shareDecryptResult" style="display: none;">
            <div style="margin-bottom: 24px;">
                <label style="font-size: 11px; color: var(--color-text-secondary);
                              letter-spacing: 0.1em; text-transform: uppercase; display: block; margin-bottom: 10px;">
                    PASSWORD
                </label>
                <div style="display: flex; align-items: center; gap: 16px;">
                    <span id="sharedPasswordDisplay"
                          style="font-family: 'Space Grotesk'; font-size: 28px;
                                 letter-spacing: 0.05em; flex: 1; word-break: break-all;
                                 color: var(--color-text-primary);"></span>
                    <button type="button" id="copySharedPasswordBtn"
                            title="Copy password"
                            style="background: none; border: none;
                                   color: var(--color-text-secondary); cursor: pointer; padding: 4px;">
                        <span class="material-symbols-outlined">content_copy</span>
                    </button>
                </div>
            </div>

            <div style="height: 1px; background: var(--color-border); margin: 24px 0;"></div>

            <p style="font-size: 13px; color: var(--color-text-secondary); margin-bottom: 20px; line-height: 1.5;">
                Add this entry to your vault to keep it accessible like any other saved password.
            </p>

            {{-- Step 2a: vault password prompt --}}
            <div id="vaultPasswordSection">
                <div style="margin-bottom: 16px;">
                    <label style="font-size: 11px; color: var(--color-text-secondary);
                                  letter-spacing: 0.1em; text-transform: uppercase; display: block; margin-bottom: 8px;">
                        YOUR VAULT PASSWORD
                    </label>
                    <input type="password" id="vaultPasswordInput"
                           placeholder="Enter your vault password to save…"
                           autocomplete="current-password"
                           style="width: 100%; background: transparent; border: none;
                                  border-bottom: 1px solid var(--color-border); padding: 10px 0;
                                  color: var(--color-text-primary); font-family: inherit; font-size: 14px;">
                </div>

                <p id="vaultSaveError"
                   style="color: #ff3d3d; font-size: 13px; display: none; margin-bottom: 12px;"></p>

                <button type="button" id="addToVaultBtn" class="btn-primary" style="width: 100%;">
                    ADD TO MY VAULT <span class="material-symbols-outlined">save</span>
                </button>
            </div>

            {{-- Step 2b: success --}}
            <div id="vaultSaveSuccess" style="display: none; text-align: center; padding: 16px 0;">
                <span class="material-symbols-outlined" style="font-size: 48px; color: #4caf50; display: block; margin-bottom: 12px;">check_circle</span>
                <p style="font-size: 15px; color: var(--color-text-primary); margin-bottom: 20px;">Entry saved to your vault!</p>
                <a href="/dashboard" class="btn-primary" style="text-decoration: none; display: inline-flex;">
                    BACK TO DASHBOARD
                </a>
            </div>
        </div>
    </div>

    <p style="margin-top: 24px; font-size: 13px; color: var(--color-text-secondary);">
        <a href="/dashboard">← Back to dashboard</a>
    </p>
</div>
@endsection

@section('footer')
    <x-footer/>
@endsection

@push('scripts')
<script>
(function () {
    const shareId = {{ $share->id }};
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    // Pre-fill code from URL fragment (#key=...) and show auto-decrypt state
    const hash = window.location.hash;
    const hasFragment = hash.startsWith('#key=');
    if (hasFragment) {
        document.getElementById('shareCodeInput').value = decodeURIComponent(hash.slice(5));
        document.getElementById('shareDecryptPrompt').style.display = 'none';
        document.getElementById('shareAutoDecrypt').style.display = '';
    }

    function b64Decode(b64) {
        const bin = atob(b64);
        const out = new Uint8Array(bin.length);
        for (let i = 0; i < bin.length; i++) out[i] = bin.charCodeAt(i);
        return out;
    }

    function b64Encode(buf) {
        let bin = '';
        new Uint8Array(buf).forEach(b => bin += String.fromCharCode(b));
        return btoa(bin);
    }

    function hexToBytes(hex) {
        const out = new Uint8Array(hex.length / 2);
        for (let i = 0; i < out.length; i++) out[i] = parseInt(hex.slice(i * 2, i * 2 + 2), 16);
        return out;
    }

    async function decryptWithKey(shareKeyB64, ciphertextB64, ivB64) {
        const keyBytes = b64Decode(shareKeyB64);
        const cryptoKey = await crypto.subtle.importKey(
            'raw', keyBytes, { name: 'AES-GCM' }, false, ['decrypt']
        );
        const plainBuf = await crypto.subtle.decrypt(
            { name: 'AES-GCM', iv: b64Decode(ivB64) },
            cryptoKey,
            b64Decode(ciphertextB64)
        );
        return new TextDecoder().decode(plainBuf);
    }

    async function deriveVaultKey(password, saltHex, params) {
        const keyMaterial = await crypto.subtle.importKey(
            'raw', new TextEncoder().encode(password),
            { name: 'PBKDF2' }, false, ['deriveKey']
        );
        return crypto.subtle.deriveKey(
            {
                name: 'PBKDF2',
                salt: hexToBytes(saltHex),
                iterations: params.iterations,
                hash: params.hash,
            },
            keyMaterial,
            { name: 'AES-GCM', length: params.keyLen },
            false,
            ['encrypt', 'decrypt']
        );
    }

    async function encryptWithVaultKey(vaultKey, plaintext) {
        const iv = crypto.getRandomValues(new Uint8Array(12));
        const cipherBuf = await crypto.subtle.encrypt(
            { name: 'AES-GCM', iv },
            vaultKey,
            new TextEncoder().encode(plaintext)
        );
        return { encrypted_password: b64Encode(cipherBuf), iv: b64Encode(iv) };
    }

    let decryptedPassword = null;

    // Decrypt step
    document.getElementById('decryptShareBtn').addEventListener('click', async () => {
        const shareCode = document.getElementById('shareCodeInput').value.trim();
        const errEl = document.getElementById('shareDecryptError');
        errEl.style.display = 'none';

        if (!shareCode) {
            errEl.textContent = 'Paste the share code first.';
            errEl.style.display = '';
            return;
        }

        try {
            const res = await fetch(`/share/fetch/${shareId}`, {
                headers: { Accept: 'application/json' },
            });
            if (!res.ok) {
                const { error } = await res.json();
                throw new Error(error || 'Failed to load share data.');
            }
            const { encrypted_payload, iv } = await res.json();

            decryptedPassword = await decryptWithKey(shareCode, encrypted_payload, iv);

            document.getElementById('sharedPasswordDisplay').textContent = decryptedPassword;
            document.getElementById('shareDecryptPrompt').style.display = 'none';
            document.getElementById('shareDecryptResult').style.display = '';
        } catch (err) {
            // If auto-decrypt failed, restore the manual prompt so the user can retry
            document.getElementById('shareAutoDecrypt').style.display = 'none';
            document.getElementById('shareDecryptPrompt').style.display = '';
            errEl.textContent = err.message.includes('decrypt')
                ? 'Decryption failed — the share link may be invalid or expired.'
                : err.message;
            errEl.style.display = '';
        }
    });

    // Copy decrypted password
    document.getElementById('copySharedPasswordBtn').addEventListener('click', () => {
        if (!decryptedPassword) return;
        navigator.clipboard.writeText(decryptedPassword).then(() => {
            const icon = document.querySelector('#copySharedPasswordBtn .material-symbols-outlined');
            icon.textContent = 'check';
            setTimeout(() => { icon.textContent = 'content_copy'; }, 2000);
        });
    });

    // Add to vault step
    document.getElementById('addToVaultBtn').addEventListener('click', async () => {
        const vaultPassword = document.getElementById('vaultPasswordInput').value;
        const errEl = document.getElementById('vaultSaveError');
        errEl.style.display = 'none';

        if (!vaultPassword) {
            errEl.textContent = 'Enter your vault password to save the entry.';
            errEl.style.display = '';
            return;
        }

        if (!decryptedPassword) {
            errEl.textContent = 'Decrypt the entry first.';
            errEl.style.display = '';
            return;
        }

        const btn = document.getElementById('addToVaultBtn');
        btn.disabled = true;
        btn.textContent = 'SAVING…';

        try {
            // Fetch vault salt
            const saltRes = await fetch('/vault/salt', { headers: { Accept: 'application/json' } });
            if (!saltRes.ok) throw new Error('Could not load vault settings.');
            const { salt, params } = await saltRes.json();

            // Derive vault key and encrypt
            const vaultKey = await deriveVaultKey(vaultPassword, salt, params);
            const { encrypted_password, iv } = await encryptWithVaultKey(vaultKey, decryptedPassword);

            // Save vault entry
            const saveRes = await fetch('/vault/entries', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' },
                body: JSON.stringify({
                    nickname: '{{ addslashes($share->label ?? 'Shared Entry') }}',
                    icon: 'share',
                    encrypted_password,
                    iv,
                }),
            });

            if (!saveRes.ok) {
                const data = await saveRes.json();
                throw new Error(data.message || 'Failed to save vault entry.');
            }

            // Mark share as claimed
            await fetch(`/share/accept/${shareId}`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' },
            });

            document.getElementById('vaultPasswordSection').style.display = 'none';
            document.getElementById('vaultSaveSuccess').style.display = '';
        } catch (err) {
            errEl.textContent = err.message.includes('decrypt')
                ? 'Wrong vault password — decryption failed.'
                : err.message;
            errEl.style.display = '';
            btn.disabled = false;
            btn.innerHTML = 'ADD TO MY VAULT <span class="material-symbols-outlined">save</span>';
        }
    });

    // Auto-trigger decrypt if key was in fragment
    if (hasFragment) {
        document.getElementById('decryptShareBtn').click();
    }
})();
</script>
@endpush
