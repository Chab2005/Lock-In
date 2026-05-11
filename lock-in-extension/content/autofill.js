/**
 * Lock In — Content Script  (autofill engine v2)
 *
 * Two independent fill paths:
 *
 *   A. INTERACTION-TRIGGERED (primary UX)
 *      Fires the moment the user clicks or focuses any auth field.
 *      Fills with the best-matching credential immediately.
 *      No popup required.
 *
 *   B. PAGE-LOAD AUTO-FILL (silent background pass)
 *      Runs after the page settles and on every DOM mutation that adds inputs.
 *      Only fills if exactly 1 credential matches (safe, no ambiguity).
 *
 * Global listeners are bound unconditionally at injection time — they do NOT
 * wait for form detection.  This guarantees the extension reacts even when
 * the form is injected after the script runs (SPAs, modals, lazy routes).
 */

(function () {
    'use strict';

    if (window.__lockInInjected) return;
    window.__lockInInjected = true;

    const ext = typeof browser !== 'undefined' ? browser : chrome;

    /* ── Debug logger ─────────────────────────────────────────────────────── */

    const log = (...a) => console.debug('[Lock In]', ...a);

    /* ── Field-classification regexes ─────────────────────────────────────── */

    const USERNAME_TYPES        = new Set(['text', 'email', 'tel']);
    const USERNAME_AUTOCOMPLETE = /^(username|email|tel|nickname|login)/i;
    const USERNAME_NAME_ID      = /user|email|login|account|mail|phone|mobile|uname|identifier|memberid|customer/i;
    const USERNAME_PLACEHOLDER  = /email|user|login|phone|mobile|sign.?in|your\s+email/i;
    const USERNAME_LABEL        = /email|user|login|phone|mobile|username|identifier/i;
    const OTP_SIGNAL            = /otp|one.?time|passcode|verify|2fa|mfa|totp|sms.?code|auth.?code/i;
    const SEARCH_NAME           = /^(q|query|search|s)$/i;

    /* ── Concurrency + session state ──────────────────────────────────────── */

    // Credential cache — refreshed every 15 s to avoid hammering the background
    // on rapid focus/click events.
    let _credCache = null; // { entries: [], ts: number }
    const CRED_CACHE_TTL = 15_000;

    // Prevents two concurrent fill attempts from racing.
    let _fillBusy = false;

    // Tracks which password/username elements we have already filled in this
    // page session, so refocusing does not re-trigger a fill.
    // WeakSet: GC'd automatically when the DOM element is removed.
    const _filledMarkers = new WeakSet();

    /* ── Helpers ──────────────────────────────────────────────────────────── */

    function getAssociatedLabel(input) {
        if (input.id) {
            try {
                const lbl = document.querySelector(`label[for="${CSS.escape(input.id)}"]`);
                if (lbl) return lbl.textContent;
            } catch (_) {}
        }
        const wrapping = input.closest('label');
        if (wrapping) return wrapping.textContent;
        const aria = input.getAttribute('aria-label');
        if (aria) return aria;
        const lbId = input.getAttribute('aria-labelledby');
        if (lbId) {
            const lbEl = document.getElementById(lbId);
            if (lbEl) return lbEl.textContent;
        }
        return '';
    }

    function isVisible(el) {
        if (!el) return false;
        if (el.disabled || el.readOnly) return false;
        const s = getComputedStyle(el);
        if (s.display === 'none' || s.visibility === 'hidden' || parseFloat(s.opacity) === 0) return false;
        const r = el.getBoundingClientRect();
        return r.width > 0 || r.height > 0;
    }

    function isOtpField(el) {
        const ac = el.getAttribute('autocomplete') || '';
        if (ac === 'one-time-code') return true;
        if (el.maxLength === 1 && el.type !== 'hidden') return true;
        const sig = `${el.name} ${el.id} ${el.placeholder} ${getAssociatedLabel(el)}`;
        return OTP_SIGNAL.test(sig);
    }

    function isSearchField(el) {
        if (el.type === 'search') return true;
        const role = el.getAttribute('role') || '';
        if (role === 'searchbox' || role === 'combobox') return true;
        if (SEARCH_NAME.test(el.name) && !USERNAME_NAME_ID.test(el.name)) return true;
        return false;
    }

    function isPasswordField(el) {
        if (el.tagName !== 'INPUT' || el.type !== 'password') return false;
        const ac = (el.getAttribute('autocomplete') || '').toLowerCase();
        return ac !== 'new-password' && !el.name.match(/confirm|repeat|retype/i);
    }

    function isUsernameField(el) {
        if (el.tagName !== 'INPUT') return false;
        const t = (el.type || 'text').toLowerCase();
        if (t && !USERNAME_TYPES.has(t)) return false;
        if (isOtpField(el))    return false;
        if (isSearchField(el)) return false;

        const ac = (el.getAttribute('autocomplete') || '').toLowerCase();
        if (USERNAME_AUTOCOMPLETE.test(ac)) return true;
        if (t === 'email')                   return true;
        if (USERNAME_NAME_ID.test(el.name))  return true;
        if (USERNAME_NAME_ID.test(el.id))    return true;
        if (USERNAME_PLACEHOLDER.test(el.placeholder))         return true;
        if (USERNAME_LABEL.test(getAssociatedLabel(el)))       return true;
        return false;
    }

    /* ── Form detection ───────────────────────────────────────────────────── */

    /**
     * Returns detected login-form pairs sorted by DOM order.
     * Each pair: { usernameInput, passwordInput, multiStep }
     * multiStep=true when only a username field is visible (step-1 of 2).
     */
    function detectLoginForms() {
        const passwordFields = Array.from(document.querySelectorAll('input[type=password]'))
            .filter(el => isVisible(el) && isPasswordField(el));
        const usernameFields = Array.from(document.querySelectorAll('input'))
            .filter(el => isVisible(el) && isUsernameField(el));

        log(`Scan — username candidates: ${usernameFields.length}, password fields: ${passwordFields.length}`);

        const pairs  = [];
        const usedPw = new Set();
        const usedUsr = new Set();

        for (const pw of passwordFields) {
            if (usedPw.has(pw)) continue;
            const form = pw.closest('form');
            let best = null;

            for (const u of usernameFields) {
                if (usedUsr.has(u)) continue;
                if (form && !form.contains(u)) continue;
                // u must precede pw in document order
                if (!(u.compareDocumentPosition(pw) & Node.DOCUMENT_POSITION_FOLLOWING)) continue;
                if (!best) { best = u; continue; }
                // prefer whichever username field is closer (later) before pw
                if (best.compareDocumentPosition(u) & Node.DOCUMENT_POSITION_FOLLOWING) best = u;
            }

            usedPw.add(pw);
            if (best) usedUsr.add(best);
            pairs.push({ usernameInput: best, passwordInput: pw, multiStep: false });
        }

        // Multi-step: username visible but password not yet
        if (passwordFields.length === 0) {
            for (const u of usernameFields) {
                if (usedUsr.has(u)) continue;
                log('Multi-step form detected (username-only screen)');
                pairs.push({ usernameInput: u, passwordInput: null, multiStep: true });
                break;
            }
        }

        return pairs;
    }

    /**
     * Given an element the user just interacted with, return the form pair
     * it belongs to (or the best pair if it's unambiguous).
     */
    function findPairForElement(el) {
        const pairs = detectLoginForms();
        if (!pairs.length) return null;

        // Try to find the pair that contains this element
        for (const p of pairs) {
            if (p.usernameInput === el || p.passwordInput === el) return p;
        }

        // Fall back to first pair (most common — single login form)
        return pairs[0];
    }

    /* ── Native-value setter (React / Vue / Angular compat) ──────────────── */

    const nativeSetter = Object.getOwnPropertyDescriptor(
        window.HTMLInputElement.prototype, 'value',
    )?.set;

    function setNativeValue(el, value) {
        if (nativeSetter) {
            nativeSetter.call(el, value);
        } else {
            el.value = value;
        }
        el.dispatchEvent(new Event('input',  { bubbles: true }));
        el.dispatchEvent(new Event('change', { bubbles: true }));
        el.dispatchEvent(new KeyboardEvent('keydown', { bubbles: true }));
        el.dispatchEvent(new KeyboardEvent('keyup',   { bubbles: true }));
    }

    function fillField(el, value) {
        if (!el || value == null || value === '') return;
        el.focus({ preventScroll: true });
        setNativeValue(el, value);
        log(`  Filled [${el.type || 'text'}] name="${el.name || ''}" id="${el.id || ''}"`);
    }

    function fillPair(usernameInput, passwordInput, username, password) {
        if (usernameInput && username) fillField(usernameInput, username);
        if (passwordInput && password) fillField(passwordInput, password);
        if (passwordInput)      passwordInput.focus({ preventScroll: true });
        else if (usernameInput) usernameInput.focus({ preventScroll: true });
    }

    /* ── Domain matching ──────────────────────────────────────────────────── */

    function normalizeHost(raw) {
        try {
            const url = raw.startsWith('http') ? new URL(raw) : new URL('https://' + raw);
            return url.hostname.replace(/^www\./, '').toLowerCase();
        } catch {
            return raw.toLowerCase().replace(/^www\./, '');
        }
    }

    function domainMatches(entryWebsite, pageHost) {
        if (!entryWebsite) return false;
        const entry = normalizeHost(entryWebsite);
        const page  = pageHost.replace(/^www\./, '').toLowerCase();
        return entry === page
            || page.endsWith('.' + entry)
            || entry.endsWith('.' + page);
    }

    /* ── Credential retrieval ─────────────────────────────────────────────── */

    /**
     * Returns vault entries matching the current page's domain.
     * Caches for CRED_CACHE_TTL ms to avoid hammering the background
     * when the user rapidly focuses/clicks multiple fields.
     */
    async function getMatchingCredentials(forceRefresh = false) {
        const now = Date.now();
        if (!forceRefresh && _credCache && now - _credCache.ts < CRED_CACHE_TTL) {
            log(`Credential cache hit (${_credCache.entries.length} entries)`);
            return _credCache.entries;
        }

        try {
            const auth = await ext.runtime.sendMessage({ action: 'CHECK_AUTH' });
            if (!auth?.ok || !auth.authenticated || !auth.keyReady) {
                log('Not authenticated — autofill unavailable');
                return [];
            }

            const res = await ext.runtime.sendMessage({ action: 'GET_ENTRIES' });
            if (!res?.ok || !Array.isArray(res.entries)) return [];

            const page    = window.location.hostname;
            const matched = res.entries.filter(e => domainMatches(e.website, page));

            _credCache = { entries: matched, ts: Date.now() };
            log(`Credentials fetched — ${matched.length} match(es) for ${page}`);
            return matched;
        } catch (e) {
            log('Error fetching credentials:', e);
            return [];
        }
    }

    async function decryptEntry(entry) {
        try {
            const res = await ext.runtime.sendMessage({ action: 'DECRYPT_ENTRY_ONLY', entry });
            return res?.ok ? res.password : null;
        } catch {
            return null;
        }
    }

    /* ── Fill execution ───────────────────────────────────────────────────── */

    /**
     * Decrypts and fills a credential pair.
     * Returns true on success.
     */
    async function executeFill(pair, entry) {
        const { usernameInput, passwordInput, multiStep } = pair;

        let password = null;
        if (!multiStep && passwordInput) {
            password = await decryptEntry(entry);
            if (!password) {
                log('Decryption failed — aborting fill');
                return false;
            }
        }

        const username = entry.email_hint || '';
        fillPair(usernameInput, passwordInput, username, password);

        // Mark the anchor element so we skip re-fills on refocus
        const marker = passwordInput || usernameInput;
        if (marker) _filledMarkers.add(marker);

        log(`Filled with "${entry.nickname || entry.website || 'entry'}"`);
        return true;
    }

    /* ═══════════════════════════════════════════════════════════════════════
     *  PATH A — INTERACTION-TRIGGERED FILL
     *  Fires when the user explicitly clicks or focuses an auth field.
     *  More aggressive: fills with best match even when multiple exist.
     * ═══════════════════════════════════════════════════════════════════════ */

    // Debounce timer — prevents duplicate triggers when both focusin AND click
    // fire for the same user gesture.
    let _interactionTimer = null;

    async function onAuthFieldInteraction(el) {
        // Fast-path: we already filled this specific field — user is just clicking
        // around the form, don't refill.
        const marker = el.type === 'password'
            ? el
            : el.closest('form')?.querySelector('input[type=password]') || el;

        if (_filledMarkers.has(marker)) {
            log(`Field already filled — skipping re-fill`);
            return;
        }

        if (_fillBusy) return;

        const pair = findPairForElement(el);
        if (!pair) {
            log('No login pair detected for focused element');
            return;
        }

        const creds = await getMatchingCredentials();
        if (!creds.length) {
            log('No credentials match this domain — interaction fill skipped');
            return;
        }

        // Pick the best credential: if only 1 matches, use it; if multiple,
        // use the first (most-recently-added in vault order).
        const entry = creds[0];
        if (creds.length > 1) {
            log(`${creds.length} credentials available — using first: "${entry.nickname || entry.website}"`);
        }

        _fillBusy = true;
        try {
            await executeFill(pair, entry);
        } finally {
            _fillBusy = false;
        }
    }

    function scheduleInteractionFill(el) {
        clearTimeout(_interactionTimer);
        _interactionTimer = setTimeout(() => {
            onAuthFieldInteraction(el).catch(err =>
                log('Interaction fill error:', err),
            );
        }, 50); // 50 ms — absorbs duplicate focusin+click for same gesture
    }

    /* ═══════════════════════════════════════════════════════════════════════
     *  PATH B — PAGE-LOAD AUTO-FILL
     *  Silent background pass; only fires when exactly 1 credential matches.
     * ═══════════════════════════════════════════════════════════════════════ */

    let _autoInProgress = false;

    async function autoFillPage() {
        if (_autoInProgress) return;
        _autoInProgress = true;

        try {
            const pairs = detectLoginForms();
            if (!pairs.length) return;

            const creds = await getMatchingCredentials();

            if (creds.length === 0) {
                log('Auto-fill: no matching credentials');
                return;
            }
            if (creds.length > 1) {
                log(`Auto-fill: ${creds.length} matches — waiting for user to interact`);
                return;
            }

            const pair   = pairs[0];
            const marker = pair.passwordInput || pair.usernameInput;

            if (marker && _filledMarkers.has(marker)) {
                log('Auto-fill: already filled this session');
                return;
            }

            log('Auto-fill: 1 credential match — filling silently');
            await executeFill(pair, creds[0]);
        } finally {
            _autoInProgress = false;
        }
    }

    /* ── Form-submit capture (save-credential prompt) ─────────────────────── */

    function bindSavePrompt(pairs) {
        for (const { usernameInput, passwordInput } of pairs) {
            if (!passwordInput) continue;
            if (passwordInput.dataset.lockinBound) continue;
            passwordInput.dataset.lockinBound = 'true';

            const capture = () => {
                const email    = usernameInput?.value.trim() || '';
                const password = passwordInput.value;
                if (!password) return;
                ext.runtime.sendMessage({
                    action:   'CREDENTIAL_SUBMITTED',
                    email,
                    password,
                    url:      window.location.href,
                    hostname: window.location.hostname,
                }).catch(() => {});
            };

            const form = passwordInput.closest('form');
            if (form) {
                form.addEventListener('submit', capture);
            } else {
                document.querySelectorAll('button[type=submit], button:not([type]), input[type=submit]')
                    .forEach(btn => btn.addEventListener('click', capture));
            }
        }
    }

    /* ── SPA navigation detection ─────────────────────────────────────────── */

    let _lastUrl = location.href;

    function pollUrlChange() {
        if (location.href === _lastUrl) return;
        _lastUrl = location.href;
        log('SPA navigation — resetting fill state');
        _credCache = null; // force credential re-fetch for new page
        schedulePageScan(150);
    }

    setInterval(pollUrlChange, 800);

    /* ── MutationObserver for dynamically loaded forms ────────────────────── */

    let _scanDebounce = null;

    function schedulePageScan(delay = 400) {
        clearTimeout(_scanDebounce);
        _scanDebounce = setTimeout(async () => {
            const pairs = detectLoginForms();
            if (!pairs.length) return;
            bindSavePrompt(pairs);
            await autoFillPage();
        }, delay);
    }

    const observer = new MutationObserver((mutations) => {
        const hasNewInputs = mutations.some(m =>
            Array.from(m.addedNodes).some(n =>
                n.nodeType === Node.ELEMENT_NODE &&
                (n.tagName === 'INPUT' ||
                    (typeof n.querySelector === 'function' && n.querySelector('input'))),
            ),
        );
        if (hasNewInputs) schedulePageScan(350);
    });

    function startObserver() {
        const target = document.body || document.documentElement;
        observer.observe(target, { childList: true, subtree: true });
    }

    /* ── Global interaction listeners (bound unconditionally at startup) ───── */

    /**
     * These are registered ONCE, at injection time, before any form detection.
     * capture: true ensures we see the event even if the page calls stopPropagation.
     * passive: true keeps scroll performance intact (we never call preventDefault).
     */
    function bindGlobalListeners() {
        const opts = { capture: true, passive: true };

        document.addEventListener('focusin', (e) => {
            const el = e.target;
            if (!(el instanceof HTMLInputElement)) return;
            if (!isUsernameField(el) && !isPasswordField(el)) return;
            log(`focusin on ${el.type || 'text'} field — scheduling fill`);
            scheduleInteractionFill(el);
        }, opts);

        document.addEventListener('click', (e) => {
            const el = e.target;
            if (!(el instanceof HTMLInputElement)) return;
            if (!isUsernameField(el) && !isPasswordField(el)) return;
            log(`click on ${el.type || 'text'} field — scheduling fill`);
            scheduleInteractionFill(el);
        }, opts);
    }

    /* ── Message listener (popup / background → content) ─────────────────── */

    ext.runtime.onMessage.addListener((msg, _sender, sendResponse) => {
        if (msg.action === 'AUTOFILL_FILL') {
            // Manual fill dispatched from the background (popup button).
            // Bypasses the interaction-fill cache so the user can always
            // override the auto-choice.
            manualFill(msg.username, msg.password)
                .then(sendResponse)
                .catch(err => sendResponse({ filled: false, error: err.message }));
            return true;
        }
    });

    /**
     * Manual fill — called when the user explicitly picks an entry from the popup.
     * Clears the fill cache for the target element so it overrides any prior fill.
     */
    async function manualFill(username, password) {
        const pairs = detectLoginForms();

        if (pairs.length > 0) {
            const { usernameInput, passwordInput, multiStep } = pairs[0];
            const marker = passwordInput || usernameInput;

            // Allow popup-initiated fill to override a previous auto-fill
            if (marker) _filledMarkers.delete(marker);

            fillPair(usernameInput, passwordInput, username, multiStep ? null : password);
            if (marker) _filledMarkers.add(marker);
            return { filled: true, pairs: pairs.length };
        }

        // Fallback: independently find each field type
        log('Manual fill — no pair detected, using fallback search');
        const pw  = Array.from(document.querySelectorAll('input[type=password]')).find(isVisible);
        const usr = Array.from(document.querySelectorAll('input')).find(el => isVisible(el) && isUsernameField(el));

        let filled = false;
        if (usr && username) { fillField(usr, username); filled = true; }
        if (pw  && password) { fillField(pw,  password); filled = true; }

        return { filled, pairs: 0, method: 'fallback' };
    }

    /* ── Bootstrap ────────────────────────────────────────────────────────── */

    // 1. Bind global listeners first — they must exist before any form loads.
    bindGlobalListeners();

    // 2. Start DOM observer.
    if (document.body) {
        startObserver();
    } else {
        document.addEventListener('DOMContentLoaded', startObserver, { once: true });
    }

    // 3. Initial auto-fill pass after page settles.
    schedulePageScan(500);

    log('Content script loaded — interaction-triggered autofill active');

})();
