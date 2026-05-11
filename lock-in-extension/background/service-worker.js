/**
 * Lock In — Background service (MV2 persistent background page)
 *
 * ── Key management ────────────────────────────────────────────────────────────
 * The vault key is derived from the login password via PBKDF2 (same algorithm
 * as the web app's VaultCrypto class in vault.js).  A "verifier" blob is stored
 * on the server: VERIFIER_PLAINTEXT encrypted with the derived key.  Decrypting
 * that blob at login time proves the login password matches the vault key — if
 * it fails, we know the user set a different vault password on the website and
 * we surface a clear error instead of silently using the wrong key.
 *
 * ── Key lifecycle ─────────────────────────────────────────────────────────────
 *   Set     : MSG.LOGIN (successful auth + verifier check + key derivation)
 *   Cleared : MSG.LOGOUT, or when the browser closes (background page unloads)
 *   Missing : popup detects keyReady:false → shows login view (re-login re-derives)
 */

import { MSG, VERIFIER_PLAINTEXT } from '../shared/constants.js';
import { deriveKey, encrypt, decrypt }  from '../shared/crypto.js';
import {
    login as apiLogin,
    logout as apiLogout,
    checkAuth,
    fetchSalt,
    fetchVerifier,
    setVerifier,
    fetchEntries,
    createEntry,
    getAppUrl,
    setAppUrl,
} from '../shared/api.js';

const ext = typeof browser !== 'undefined' ? browser : chrome;

// ── In-memory state (cleared on browser close) ────────────────────────────────

let _vaultKey     = null; // CryptoKey — never written to disk
let _entriesCache = null; // decrypted entry list (shallow cache)
let _pendingCred  = null; // { email, password, url, hostname, ts }

// ── Message dispatcher ────────────────────────────────────────────────────────

ext.runtime.onMessage.addListener((msg, _sender) => {
    return handleMessage(msg).catch((err) => {
        console.error('[Lock In BG] Unhandled error:', err);
        return { ok: false, error: err.message };
    });
});

async function handleMessage(msg) {
    switch (msg.action) {

        // ── Auth ──────────────────────────────────────────────────────────────

        case MSG.CHECK_AUTH: {
            const authenticated = await checkAuth();
            return {
                ok:           true,
                authenticated,
                keyReady:     _vaultKey !== null,
            };
        }

        case MSG.LOGIN: {
            const { email, password } = msg;

            // Step 1 — Authenticate against the server
            const loginResult = await apiLogin(email, password);
            if (!loginResult.ok) {
                const errMsg = loginResult.data?.message
                             || `Login failed (HTTP ${loginResult.status}).`;
                return { ok: false, error: errMsg };
            }

            // Step 2 — Derive vault key from login password + verify it matches
            //          the key used to set up the vault.
            //
            // WHY: The extension uses the login password as the vault key source.
            // The website uses a separate vault password.  Both hash through the
            // same per-user PBKDF2 salt stored on the server.  If the user set
            // their vault password equal to their login password, the keys are
            // identical.  The verifier blob lets us confirm this without a prompt.
            try {
                const { salt, params, has_verifier } = await fetchSalt();

                console.debug('[Lock In BG] Salt received, deriving key…', {
                    saltLen:    salt.length / 2,
                    iterations: params.iterations,
                    hash:       params.hash,
                    keyLen:     params.keyLen,
                    hasVerifier: has_verifier,
                });

                const derivedKey = await deriveKey(password, salt, params);

                if (has_verifier) {
                    // Verify the derived key against the stored verifier blob
                    const { verifier } = await fetchVerifier();
                    const dotIdx = verifier.lastIndexOf('.');

                    if (dotIdx === -1) {
                        // Malformed verifier — treat as unset and recreate
                        console.warn('[Lock In BG] Malformed verifier blob, recreating.');
                        const { ciphertext, iv } = await encrypt(derivedKey, VERIFIER_PLAINTEXT);
                        await setVerifier(`${ciphertext}.${iv}`);
                    } else {
                        const ciphertextB64 = verifier.slice(0, dotIdx);
                        const ivB64         = verifier.slice(dotIdx + 1);

                        let decrypted;
                        try {
                            decrypted = await decrypt(derivedKey, ciphertextB64, ivB64);
                        } catch (decryptErr) {
                            console.warn('[Lock In BG] Verifier decrypt failed — key mismatch:', decryptErr);
                            return {
                                ok:    false,
                                error: 'Your vault uses a different password than your login password. '
                                     + 'On the Lock In website, unlock your vault with your vault password, '
                                     + 'then go to Settings → Vault to align both passwords.',
                            };
                        }

                        if (decrypted !== VERIFIER_PLAINTEXT) {
                            console.warn('[Lock In BG] Verifier plaintext mismatch.');
                            return {
                                ok:    false,
                                error: 'Vault key verification failed. '
                                     + 'Your vault password does not match your login password.',
                            };
                        }

                        console.debug('[Lock In BG] Verifier OK — key confirmed.');
                    }
                } else {
                    // First vault use for this user — create the verifier so future
                    // logins can confirm the key without a password prompt.
                    console.debug('[Lock In BG] No verifier found — creating for first use.');
                    const { ciphertext, iv } = await encrypt(derivedKey, VERIFIER_PLAINTEXT);
                    await setVerifier(`${ciphertext}.${iv}`);
                }

                _vaultKey = derivedKey;
                console.debug('[Lock In BG] Vault key ready.');

            } catch (setupErr) {
                // Network error, 401 on /salt or /verifier, etc.
                console.error('[Lock In BG] Vault key setup failed:', setupErr);
                return {
                    ok:    false,
                    error: 'Could not prepare the vault key. '
                         + 'Check your connection and try again.',
                };
            }

            return { ok: true };
        }

        case MSG.LOGOUT: {
            await apiLogout();
            _vaultKey     = null;
            _entriesCache = null;
            _pendingCred  = null;
            return { ok: true };
        }

        // ── Vault entries ─────────────────────────────────────────────────────

        case MSG.GET_ENTRIES: {
            let entries = _entriesCache;
            if (!entries || msg.forceRefresh) {
                entries = await fetchEntries();
                _entriesCache = entries;
            }
            return { ok: true, entries };
        }

        // ── Autofill ──────────────────────────────────────────────────────────

        case MSG.AUTOFILL_REQUEST: {
            const { tabId, entry } = msg;

            if (!_vaultKey) {
                return { ok: false, error: 'Vault key not ready. Please log in again.' };
            }

            let password;
            try {
                password = await decrypt(_vaultKey, entry.encrypted_password, entry.iv);
            } catch (decErr) {
                console.error('[Lock In BG] Autofill decrypt failed:', decErr, {
                    entryId: entry.id,
                    ivLen:   entry.iv?.length,
                });
                return { ok: false, error: 'Decryption failed. Entry may have been encrypted with a different key.' };
            }

            try {
                await ext.tabs.sendMessage(tabId, {
                    action:   MSG.AUTOFILL_FILL,
                    username: entry.email_hint || '',
                    password,
                });
            } catch {
                return { ok: false, error: 'Could not reach page. Try reloading.' };
            }

            return { ok: true };
        }

        // ── Save credential flow ──────────────────────────────────────────────

        case MSG.CREDENTIAL_SUBMITTED: {
            const { email, password, url, hostname } = msg;
            _pendingCred = { email, password, url, hostname, ts: Date.now() };
            return { ok: true };
        }

        case MSG.GET_PENDING_CRED: {
            // Discard stale credentials (older than 5 minutes)
            if (_pendingCred && Date.now() - _pendingCred.ts > 5 * 60 * 1000) {
                _pendingCred = null;
            }
            return { ok: true, credential: _pendingCred };
        }

        case MSG.CLEAR_PENDING_CRED: {
            _pendingCred = null;
            return { ok: true };
        }

        case MSG.DECRYPT_ENTRY_ONLY: {
            const { entry } = msg;

            if (!_vaultKey) {
                return { ok: false, error: 'Vault key not ready. Please log in again.' };
            }

            try {
                const password = await decrypt(_vaultKey, entry.encrypted_password, entry.iv);
                return { ok: true, password };
            } catch (decErr) {
                console.error('[Lock In BG] Decrypt-only failed:', decErr, {
                    entryId: entry.id,
                    ivLen:   entry.iv?.length,
                });
                return { ok: false, error: 'Decryption failed. Entry may have been encrypted with a different key.' };
            }
        }

        case MSG.SAVE_ENTRY: {
            const { nickname, website, email_hint, password, icon } = msg;

            if (!_vaultKey) {
                return { ok: false, error: 'Vault key not ready. Please log in again.' };
            }

            let ciphertext, iv;
            try {
                ({ ciphertext, iv } = await encrypt(_vaultKey, password));
            } catch (encErr) {
                console.error('[Lock In BG] Encrypt failed:', encErr);
                return { ok: false, error: 'Encryption failed.' };
            }

            const created = await createEntry({
                nickname,
                website,
                email_hint,
                icon:               icon || 'lock',
                encrypted_password: ciphertext,
                iv,
            });

            _entriesCache = null; // invalidate so next load fetches fresh data
            return { ok: true, id: created.id };
        }

        // ── Settings ──────────────────────────────────────────────────────────

        case MSG.GET_APP_URL: {
            const url = await getAppUrl();
            return { ok: true, url };
        }

        case MSG.SET_APP_URL: {
            await setAppUrl(msg.url);
            return { ok: true };
        }

        default:
            return { ok: false, error: `Unknown action: ${msg.action}` };
    }
}
