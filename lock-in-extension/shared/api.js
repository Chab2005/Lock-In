/**
 * Laravel API client for the Lock In extension.
 *
 * Uses Bearer token auth — avoids SameSite cookie restrictions entirely.
 * Token is persisted in browser.storage.local (survives browser restarts).
 */

import { DEFAULT_APP_URL, KEY_APP_URL } from './constants.js';

const ext = typeof browser !== 'undefined' ? browser : chrome;
const TOKEN_KEY = 'extensionToken';

// ── Base URL ──────────────────────────────────────────────────────────────────

export async function getAppUrl() {
    const result = await ext.storage.local.get(KEY_APP_URL);
    return (result[KEY_APP_URL] || DEFAULT_APP_URL).replace(/\/$/, '');
}

export async function setAppUrl(url) {
    await ext.storage.local.set({ [KEY_APP_URL]: url.replace(/\/$/, '') });
}

// ── Token storage ─────────────────────────────────────────────────────────────

async function getToken() {
    const result = await ext.storage.local.get(TOKEN_KEY);
    return result[TOKEN_KEY] || null;
}

async function saveToken(token) {
    await ext.storage.local.set({ [TOKEN_KEY]: token });
}

async function clearToken() {
    await ext.storage.local.remove(TOKEN_KEY);
}

// ── Core fetch wrapper ────────────────────────────────────────────────────────

async function apiFetch(path, options = {}) {
    const appUrl = await getAppUrl();
    const url    = `${appUrl}/ext${path}`;
    const token  = await getToken();

    const headers = {
        Accept:         'application/json',
        'Content-Type': 'application/json',
        ...options.headers,
    };

    if (token) {
        headers['Authorization'] = `Bearer ${token}`;
    }

    const method = (options.method || 'GET').toUpperCase();

    return fetch(url, { ...options, method, headers });
}

// ── Auth endpoints ────────────────────────────────────────────────────────────

export async function login(email, password) {
    const appUrl = await getAppUrl();
    const res = await fetch(`${appUrl}/ext/login`, {
        method:  'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
        body:    JSON.stringify({ email, password }),
    });
    const data = await res.json().catch(() => ({}));

    if (res.ok && data.token) {
        await saveToken(data.token);
    }

    return { ok: res.ok, status: res.status, data };
}

export async function logout() {
    await apiFetch('/logout', { method: 'POST' }).catch(() => {});
    await clearToken();
}

export async function checkAuth() {
    try {
        const token = await getToken();
        if (!token) return false;
        const res = await apiFetch('/check');
        return res.ok;
    } catch {
        return false;
    }
}

// ── Vault key endpoints ───────────────────────────────────────────────────────

/**
 * Fetch the per-user PBKDF2 salt and parameters.
 * Creates the salt on first call (server-side lazy init).
 * @returns {Promise<{ salt: string, params: object, has_verifier: boolean }>}
 */
export async function fetchSalt() {
    const res = await apiFetch('/vault/salt');
    if (!res.ok) throw new Error(`Failed to fetch vault salt (HTTP ${res.status})`);
    return res.json();
}

/**
 * Fetch the encrypted verifier blob used to confirm the key is correct.
 * Format: "<ciphertextB64>.<ivB64>"
 * @returns {Promise<{ verifier: string }>}
 */
export async function fetchVerifier() {
    const res = await apiFetch('/vault/verifier');
    if (!res.ok) throw new Error(`Failed to fetch verifier (HTTP ${res.status})`);
    return res.json();
}

/**
 * Store the encrypted verifier blob on the server.
 * Called once after first key derivation (no verifier exists yet).
 * @param {string} verifier  "<ciphertextB64>.<ivB64>"
 */
export async function setVerifier(verifier) {
    const res = await apiFetch('/vault/verifier', {
        method: 'POST',
        body:   JSON.stringify({ verifier }),
    });
    if (!res.ok) throw new Error(`Failed to set verifier (HTTP ${res.status})`);
}

// ── Vault entry endpoints ─────────────────────────────────────────────────────

export async function fetchEntries() {
    const res = await apiFetch('/vault/entries');
    if (!res.ok) {
        if (res.status === 401) throw new Error('unauthenticated');
        throw new Error('Failed to fetch vault entries');
    }
    return res.json();
}

export async function createEntry(payload) {
    const res = await apiFetch('/vault/entries', {
        method: 'POST',
        body:   JSON.stringify(payload),
    });
    if (!res.ok) {
        const err = await res.json().catch(() => ({}));
        throw new Error(err.message || 'Failed to create vault entry');
    }
    return res.json();
}

export async function deleteEntry(id) {
    const res = await apiFetch(`/vault/entries/${id}`, { method: 'DELETE' });
    if (!res.ok) throw new Error('Failed to delete entry');
}
