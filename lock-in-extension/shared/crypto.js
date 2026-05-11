/**
 * Lock In — Cryptography helpers
 *
 * REFERENCE: This implementation is intentionally identical to VaultCrypto in
 * resources/js/vault.js. Any change here must be mirrored there, and vice versa.
 *
 * Algorithm : AES-256-GCM
 * Key source : PBKDF2(loginPassword, perUserSalt, 310 000 iters, SHA-256) → 256-bit key
 * IV         : 12 random bytes per encryption, stored alongside ciphertext
 * Encoding   : standard Base64 (btoa/atob), no URL-safe variant
 */

// ── Encoding helpers ──────────────────────────────────────────────────────────
// These produce byte-for-byte identical output to the website's helpers.

export function b64Encode(buffer) {
    // Loop form avoids the stack-overflow risk of spread on large arrays while
    // producing the same btoa() output as: btoa(String.fromCharCode(...bytes))
    const bytes = new Uint8Array(buffer);
    let bin = '';
    for (const b of bytes) bin += String.fromCharCode(b);
    return btoa(bin);
}

export function b64Decode(b64) {
    const bin = atob(b64);
    const bytes = new Uint8Array(bin.length);
    for (let i = 0; i < bin.length; i++) bytes[i] = bin.charCodeAt(i);
    return bytes;
}

function hexToBytes(hex) {
    const bytes = new Uint8Array(hex.length / 2);
    for (let i = 0; i < bytes.length; i++) {
        bytes[i] = parseInt(hex.slice(i * 2, i * 2 + 2), 16);
    }
    return bytes;
}

// ── Key derivation ────────────────────────────────────────────────────────────

/**
 * Derive an AES-256-GCM CryptoKey from a password via PBKDF2.
 *
 * Matches VaultCrypto.deriveKey() in vault.js exactly:
 *   - TextEncoder('utf-8') for password bytes
 *   - hex salt → Uint8Array via hexToBytes
 *   - params: { iterations: 310000, hash: 'SHA-256', keyLen: 256 }
 *   - non-extractable (key never leaves WebCrypto)
 *   - usages: ['encrypt', 'decrypt']
 *
 * @param {string} password
 * @param {string} saltHex   Hex string from GET /ext/vault/salt
 * @param {{ iterations: number, hash: string, keyLen: number }} params
 * @returns {Promise<CryptoKey>}
 */
export async function deriveKey(password, saltHex, params) {
    const keyMaterial = await crypto.subtle.importKey(
        'raw',
        new TextEncoder().encode(password),
        'PBKDF2',
        false,
        ['deriveKey'],
    );

    const key = await crypto.subtle.deriveKey(
        {
            name:       'PBKDF2',
            salt:       hexToBytes(saltHex),
            iterations: params.iterations,
            hash:       params.hash,
        },
        keyMaterial,
        { name: 'AES-GCM', length: params.keyLen },
        false,                // non-extractable — never written to disk
        ['encrypt', 'decrypt'],
    );

    console.debug('[LockIn Crypto] Key derived:', {
        iterations: params.iterations,
        hash:       params.hash,
        keyLen:     params.keyLen,
        saltLen:    saltHex.length / 2,
    });

    return key;
}

// ── Encrypt / decrypt ─────────────────────────────────────────────────────────

/**
 * Encrypt plaintext with AES-256-GCM.
 *
 * Matches VaultCrypto.encrypt() in vault.js exactly:
 *   - 12-byte random IV (96 bits — recommended for AES-GCM)
 *   - TextEncoder for plaintext → bytes
 *   - Returns { ciphertext: base64, iv: base64 }
 *
 * @param {CryptoKey} key
 * @param {string}    plaintext
 * @returns {Promise<{ ciphertext: string, iv: string }>}
 */
export async function encrypt(key, plaintext) {
    const iv = crypto.getRandomValues(new Uint8Array(12));

    const ciphertextBuf = await crypto.subtle.encrypt(
        { name: 'AES-GCM', iv },
        key,
        new TextEncoder().encode(plaintext),
    );

    const result = {
        ciphertext: b64Encode(ciphertextBuf),
        iv:         b64Encode(iv),
    };

    console.debug('[LockIn Crypto] Encrypted:', {
        ivLen:         iv.length,
        ciphertextLen: new Uint8Array(ciphertextBuf).length,
    });

    return result;
}

/**
 * Decrypt an AES-256-GCM ciphertext.
 *
 * Matches VaultCrypto.decrypt() in vault.js exactly:
 *   - b64Decode(ivB64) for IV bytes
 *   - b64Decode(ciphertextB64) for ciphertext+auth-tag bytes
 *   - TextDecoder for output → string
 *
 * @param {CryptoKey} key
 * @param {string}    ciphertextB64
 * @param {string}    ivB64
 * @returns {Promise<string>}
 */
export async function decrypt(key, ciphertextB64, ivB64) {
    const iv         = b64Decode(ivB64);
    const ciphertext = b64Decode(ciphertextB64);

    console.debug('[LockIn Crypto] Decrypt attempt:', {
        ivLen:         iv.length,
        ciphertextLen: ciphertext.length,
    });

    const plainBuf = await crypto.subtle.decrypt(
        { name: 'AES-GCM', iv },
        key,
        ciphertext,
    );

    return new TextDecoder().decode(plainBuf);
}
