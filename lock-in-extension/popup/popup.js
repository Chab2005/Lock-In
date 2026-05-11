/**
 * Lock In — Popup script
 *
 * View flow:
 *   loading → settings (if URL not set / first run)
 *            → login   (if not authenticated OR key not in memory)
 *            → save-prompt (if pending credential)
 *            → vault   (main view)
 *
 * No vault password. No unlock screen.
 * Login = authenticated + key derived → vault immediately available.
 */

import { MSG } from '../shared/constants.js';

const ext = typeof browser !== 'undefined' ? browser : chrome;

// ── Helpers ───────────────────────────────────────────────────────────────────

function send(action, extra = {}) {
    return ext.runtime.sendMessage({ action, ...extra });
}

function $(id) { return document.getElementById(id); }

let allEntries = [];
let currentDomain = '';

// ── Toast ─────────────────────────────────────────────────────────────────────

function toast(msg, duration = 2000) {
    let el = document.querySelector('.toast');
    if (!el) {
        el = document.createElement('div');
        el.className = 'toast';
        document.body.appendChild(el);
    }
    el.textContent = msg;
    el.classList.add('show');
    setTimeout(() => el.classList.remove('show'), duration);
}

// ── View management ───────────────────────────────────────────────────────────

function showView(id) {
    document.querySelectorAll('.view').forEach(v => { v.style.display = 'none'; });
    const view = $(id);
    if (view) view.style.display = '';

    const header = $('header');
    const showHeader = id === 'view-vault' || id === 'view-save';
    if (header) header.style.display = showHeader ? '' : 'none';
}

// ── Entry rendering ───────────────────────────────────────────────────────────

function createEntryCard(entry) {
    const template = $('entryTemplate');
    const card = template.content.cloneNode(true).firstElementChild;

    card.querySelector('.entry-name').textContent =
        entry.nickname || entry.website || 'Unnamed';
    card.querySelector('.entry-user').textContent =
        entry.email_hint || '';

    card.querySelector('.entry-fill-btn').addEventListener('click', async (e) => {
        e.stopPropagation();
        await handleAutofill(entry);
    });

    card.querySelector('.entry-copy-btn').addEventListener('click', async (e) => {
        e.stopPropagation();
        await handleCopyPassword(entry);
    });

    return card;
}

function renderEntries(entries, domainEntries, currentDomain) {
    const allList    = $('allEntries');
    const domainList = $('domainEntries');
    const domainSec  = $('domainSection');
    const domainLbl  = $('domainLabel');
    const allLbl     = $('allLabel');

    allList.innerHTML    = '';
    domainList.innerHTML = '';

    if (domainEntries.length > 0) {
        domainSec.style.display = '';
        domainLbl.textContent   = currentDomain.toUpperCase();
        domainEntries.forEach(e => domainList.appendChild(createEntryCard(e)));
        allLbl.textContent = 'ALL PASSWORDS';
    } else {
        domainSec.style.display = 'none';
    }

    if (entries.length === 0) {
        allList.innerHTML = '<div class="empty-msg">No passwords saved yet.</div>';
    } else {
        entries.forEach(e => allList.appendChild(createEntryCard(e)));
    }
}

function filterEntries(query) {
    const q = query.toLowerCase().trim();
    if (!q) return allEntries;
    return allEntries.filter(e =>
        (e.nickname   || '').toLowerCase().includes(q) ||
        (e.website    || '').toLowerCase().includes(q) ||
        (e.email_hint || '').toLowerCase().includes(q),
    );
}

function matchesDomain(entry, domain) {
    if (!domain || !entry.website) return false;
    try {
        const entryHost = new URL(
            entry.website.startsWith('http') ? entry.website : `https://${entry.website}`,
        ).hostname.replace(/^www\./, '');
        const pageHost = domain.replace(/^www\./, '');
        return entryHost === pageHost || pageHost.endsWith(`.${entryHost}`);
    } catch {
        return entry.website.toLowerCase().includes(domain.toLowerCase());
    }
}

// ── Autofill ──────────────────────────────────────────────────────────────────

async function getActiveTabId() {
    const tabs = await ext.tabs.query({ active: true, currentWindow: true });
    return tabs[0]?.id ?? null;
}

async function handleAutofill(entry) {
    const tabId = await getActiveTabId();
    if (!tabId) return toast('No active tab found.');

    const result = await send(MSG.AUTOFILL_REQUEST, { tabId, entry });

    if (!result.ok) {
        toast(result.error || 'Autofill failed.');
    } else {
        toast('Filled!');
        window.close();
    }
}

async function handleCopyPassword(entry) {
    const result = await send(MSG.DECRYPT_ENTRY_ONLY, { entry });

    if (!result.ok) {
        return toast(result.error || 'Decryption failed.');
    }

    try {
        await navigator.clipboard.writeText(result.password);
        toast('Password copied!');
    } catch {
        toast('Clipboard blocked. Try autofill instead.');
    }
}

// ── Main entry point ──────────────────────────────────────────────────────────

async function init() {
    showView('view-loading');

    const tabs = await ext.tabs.query({ active: true, currentWindow: true });
    const tab  = tabs[0];
    if (tab?.url) {
        try { currentDomain = new URL(tab.url).hostname; } catch {}
    }

    const authResult = await send(MSG.CHECK_AUTH).catch(() => null);

    // Show login if: unreachable, not authenticated, or key not yet in memory.
    // The last case happens after a browser restart — re-login re-derives the key.
    if (!authResult?.ok || !authResult.authenticated || !authResult.keyReady) {
        showView('view-login');
        return;
    }

    await maybeshowSavePrompt();
}

async function maybeshowSavePrompt() {
    const result = await send(MSG.GET_PENDING_CRED);
    if (result?.credential) {
        showSaveView(result.credential);
        return;
    }
    await loadVaultView();
}

async function loadVaultView() {
    showView('view-vault');

    const result = await send(MSG.GET_ENTRIES);
    if (!result.ok) {
        if (result.error === 'unauthenticated') {
            showView('view-login');
        }
        return;
    }

    allEntries = result.entries || [];
    const domainEntries = allEntries.filter(e => matchesDomain(e, currentDomain));
    renderEntries(allEntries, domainEntries, currentDomain);
}

// ── Save prompt view ──────────────────────────────────────────────────────────

function showSaveView(cred) {
    showView('view-save');
    $('saveDomain').textContent  = cred.hostname || cred.url;
    $('saveEmail').value         = cred.email    || '';
    $('savePassword').value      = cred.password || '';
    $('saveNickname').value      = cred.hostname
        ? capitalise(cred.hostname.replace(/^www\./, ''))
        : '';
    $('saveError').style.display = 'none';
}

function capitalise(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
}

// ── Event wiring ──────────────────────────────────────────────────────────────

// Login
$('loginBtn').addEventListener('click', async () => {
    const email    = $('loginEmail').value.trim();
    const password = $('loginPassword').value;
    const errEl    = $('loginError');

    errEl.style.display = 'none';

    if (!email || !password) {
        errEl.textContent   = 'Email and password are required.';
        errEl.style.display = '';
        return;
    }

    $('loginBtn').disabled    = true;
    $('loginBtn').textContent = 'SIGNING IN…';

    let result;
    try {
        result = await send(MSG.LOGIN, { email, password });
    } catch (e) {
        result = { ok: false, error: e.message };
    } finally {
        $('loginBtn').disabled    = false;
        $('loginBtn').textContent = 'SIGN IN';
    }

    if (result.ok) {
        await maybeshowSavePrompt();
    } else {
        errEl.textContent   = result.error || 'Login failed.';
        errEl.style.display = '';
    }
});

$('loginEmail').addEventListener('keydown', e => { if (e.key === 'Enter') $('loginPassword').focus(); });
$('loginPassword').addEventListener('keydown', e => { if (e.key === 'Enter') $('loginBtn').click(); });

$('loginSettingsBtn').addEventListener('click', async () => {
    showView('view-settings');
    const urlResult = await send(MSG.GET_APP_URL);
    if (urlResult?.url) $('appUrlInput').value = urlResult.url;
});

// Logout
$('logoutBtn').addEventListener('click', async () => {
    await send(MSG.LOGOUT);
    showView('view-login');
    $('loginEmail').value    = '';
    $('loginPassword').value = '';
});

// Settings
$('settingsBtn').addEventListener('click', async () => {
    showView('view-settings');
    const urlResult = await send(MSG.GET_APP_URL);
    if (urlResult?.url) $('appUrlInput').value = urlResult.url;
    $('header').style.display = 'none';
});

$('cancelSettingsBtn').addEventListener('click', () => {
    init();
});

$('saveUrlBtn').addEventListener('click', async () => {
    const url   = $('appUrlInput').value.trim();
    const errEl = $('urlError');
    errEl.style.display = 'none';

    if (!url) {
        errEl.textContent   = 'Enter a valid URL.';
        errEl.style.display = '';
        return;
    }

    await send(MSG.SET_APP_URL, { url });
    toast('Saved. Reconnecting…');
    await new Promise(r => setTimeout(r, 500));
    init();
});

// Search
$('searchInput').addEventListener('input', () => {
    const q         = $('searchInput').value;
    const filtered  = filterEntries(q);
    const domainEntries = q ? [] : filtered.filter(e => matchesDomain(e, currentDomain));
    renderEntries(filtered, domainEntries, currentDomain);
});

// Save credential prompt
$('confirmSaveBtn').addEventListener('click', async () => {
    const nickname   = $('saveNickname').value.trim();
    const email_hint = $('saveEmail').value.trim();
    const password   = $('savePassword').value;
    const errEl      = $('saveError');

    errEl.style.display = 'none';

    if (!password) {
        errEl.textContent   = 'Password is required.';
        errEl.style.display = '';
        return;
    }

    $('confirmSaveBtn').disabled    = true;
    $('confirmSaveBtn').textContent = 'SAVING…';

    const pendingResult = await send(MSG.GET_PENDING_CRED);
    const website = pendingResult?.credential?.url || '';

    const result = await send(MSG.SAVE_ENTRY, {
        nickname:   nickname || new URL(website || 'https://unknown').hostname,
        website,
        email_hint,
        password,
        icon: 'lock',
    });

    $('confirmSaveBtn').disabled    = false;
    $('confirmSaveBtn').textContent = 'SAVE';

    if (result.ok) {
        await send(MSG.CLEAR_PENDING_CRED);
        toast('Saved to vault!');
        await loadVaultView();
    } else {
        errEl.textContent   = result.error || 'Failed to save.';
        errEl.style.display = '';
    }
});

$('dismissSaveBtn').addEventListener('click', async () => {
    await send(MSG.CLEAR_PENDING_CRED);
    await loadVaultView();
});

// ── Bootstrap ─────────────────────────────────────────────────────────────────

init();
