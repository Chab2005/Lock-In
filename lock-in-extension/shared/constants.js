/** Default backend URL — update via popup settings or change here. */
export const DEFAULT_APP_URL = 'http://localhost';

/**
 * Known plaintext encrypted with the vault key and stored on the server.
 * Decrypting it successfully proves the derived key is correct.
 * Must match the value used by the web app (vault.js).
 */
export const VERIFIER_PLAINTEXT = 'LOCKIN_VAULT_V1';

/** browser.storage.local keys */
export const KEY_ENTRIES_CACHE = 'vaultEntriesCache';
export const KEY_PENDING_CRED  = 'pendingCredential';
export const KEY_APP_URL       = 'appUrl';

/** Message action names (popup ↔ background ↔ content) */
export const MSG = {
    CHECK_AUTH:           'CHECK_AUTH',
    LOGIN:                'LOGIN',
    LOGOUT:               'LOGOUT',
    GET_ENTRIES:          'GET_ENTRIES',
    AUTOFILL_REQUEST:     'AUTOFILL_REQUEST',
    AUTOFILL_FILL:        'AUTOFILL_FILL',
    AUTOFILL_RESULT:      'AUTOFILL_RESULT',
    CREDENTIAL_SUBMITTED: 'CREDENTIAL_SUBMITTED',
    GET_PENDING_CRED:     'GET_PENDING_CRED',
    CLEAR_PENDING_CRED:   'CLEAR_PENDING_CRED',
    SAVE_ENTRY:           'SAVE_ENTRY',
    DECRYPT_ENTRY_ONLY:   'DECRYPT_ENTRY_ONLY',
    GET_APP_URL:          'GET_APP_URL',
    SET_APP_URL:          'SET_APP_URL',
};
