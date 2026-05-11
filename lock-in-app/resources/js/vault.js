// ===== HELPERS =====

/**
 * Opens the delete-confirm modal and resolves true if the user confirms,
 * false if they cancel. Falls back to window.confirm on older browsers.
 */
function confirmDelete(name) {
    return new Promise((resolve) => {
        const modal = window.ModalSystem;
        if (!modal) { resolve(window.confirm('Delete this vault entry?')); return; }

        const nameEl = document.getElementById('deleteConfirmName');
        const confirmBtn = document.getElementById('deleteConfirmBtn');
        if (!nameEl || !confirmBtn) { resolve(window.confirm('Delete this vault entry?')); return; }

        nameEl.textContent = name || 'this entry';

        const onConfirm = () => {
            cleanup();
            modal.close('delete-confirm');
            resolve(true);
        };
        const onCancel = () => {
            cleanup();
            resolve(false);
        };
        const cleanup = () => {
            confirmBtn.removeEventListener('click', onConfirm);
            document.getElementById('modal-delete-confirm')
                ?.querySelector('[data-close-modal]')
                ?.removeEventListener('click', onCancel);
        };

        confirmBtn.addEventListener('click', onConfirm);
        document.getElementById('modal-delete-confirm')
            ?.querySelector('[data-close-modal]')
            ?.addEventListener('click', onCancel);

        modal.open('delete-confirm');
    });
}

function b64Encode(buffer) {
    return btoa(String.fromCharCode(...new Uint8Array(buffer)));
}

function b64Decode(b64) {
    const binary = atob(b64);
    const bytes = new Uint8Array(binary.length);
    for (let i = 0; i < binary.length; i++) {
        bytes[i] = binary.charCodeAt(i);
    }
    return bytes;
}

function hexToBytes(hex) {
    const bytes = new Uint8Array(hex.length / 2);
    for (let i = 0; i < bytes.length; i++) {
        bytes[i] = parseInt(hex.slice(i * 2, i * 2 + 2), 16);
    }
    return bytes;
}

function getCsrfToken() {
    const token = document.querySelector('meta[name="csrf-token"]');
    return token ? token.getAttribute('content') : '';
}

function vaultToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    document.body.appendChild(toast);
    setTimeout(() => toast.classList.add('show'), 10);
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// ===== VAULT CRYPTO =====

class VaultCrypto {
    #key = null;

    get isUnlocked() {
        return this.#key !== null;
    }

    lock() {
        this.#key = null;
    }

    async deriveKey(password, saltHex, params) {
        const enc = new TextEncoder();
        const keyMaterial = await crypto.subtle.importKey(
            'raw',
            enc.encode(password),
            'PBKDF2',
            false,
            ['deriveKey'],
        );

        const saltBytes = hexToBytes(saltHex);

        this.#key = await crypto.subtle.deriveKey(
            {
                name: 'PBKDF2',
                salt: saltBytes,
                iterations: params.iterations,
                hash: params.hash,
            },
            keyMaterial,
            { name: 'AES-GCM', length: params.keyLen },
            false,
            ['encrypt', 'decrypt'],
        );
    }

    async encrypt(plaintext) {
        if (!this.#key) throw new Error('Vault is locked');

        const enc = new TextEncoder();
        const iv = crypto.getRandomValues(new Uint8Array(12));
        const ciphertextBuffer = await crypto.subtle.encrypt(
            { name: 'AES-GCM', iv },
            this.#key,
            enc.encode(plaintext),
        );

        return {
            ciphertext: b64Encode(ciphertextBuffer),
            iv: b64Encode(iv),
        };
    }

    async decrypt(ciphertextB64, ivB64) {
        if (!this.#key) throw new Error('Vault is locked');

        const ciphertext = b64Decode(ciphertextB64);
        const iv = b64Decode(ivB64);

        const plaintextBuffer = await crypto.subtle.decrypt(
            { name: 'AES-GCM', iv },
            this.#key,
            ciphertext,
        );

        return new TextDecoder().decode(plaintextBuffer);
    }
}

// ===== VAULT MODAL =====

const VERIFIER_PLAINTEXT = 'LOCKIN_VAULT_V1';

class VaultModal {
    #crypto;
    #resolveQueue = [];

    constructor(cryptoInstance) {
        this.#crypto = cryptoInstance;
        this.modal = document.getElementById('vaultUnlockModal');
        this.passwordInput = document.getElementById('vaultUnlockPassword');
        this.unlockBtn = document.getElementById('vaultUnlockBtn');
        this.errorEl = document.getElementById('vaultUnlockError');

        if (this.unlockBtn) {
            this.unlockBtn.addEventListener('click', () => this._handleUnlock());
        }

        if (this.passwordInput) {
            this.passwordInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') this._handleUnlock();
            });
        }
    }

    demand() {
        if (this.#crypto.isUnlocked) {
            return Promise.resolve();
        }

        return new Promise((resolve) => {
            this.#resolveQueue.push(resolve);
            this._showModal();
        });
    }

    _showModal() {
        if (this.modal) {
            this.modal.style.display = 'flex';
            if (this.passwordInput) {
                this.passwordInput.value = '';
                setTimeout(() => this.passwordInput.focus(), 50);
            }
        }
    }

    _hideModal() {
        if (this.modal) {
            this.modal.style.display = 'none';
        }
    }

    _showError(msg) {
        if (this.errorEl) {
            this.errorEl.textContent = msg;
            this.errorEl.style.display = 'block';
        }
    }

    _clearError() {
        if (this.errorEl) {
            this.errorEl.textContent = '';
            this.errorEl.style.display = 'none';
        }
    }

    async _handleUnlock() {
        if (!this.passwordInput) return;

        const password = this.passwordInput.value;
        if (!password) {
            this._showError('Please enter your password.');
            return;
        }

        this._clearError();

        if (this.unlockBtn) this.unlockBtn.disabled = true;

        try {
            // 1. Fetch salt
            const saltRes = await fetch('/vault/salt', {
                headers: { Accept: 'application/json' },
            });

            if (!saltRes.ok) throw new Error('Failed to fetch vault salt');

            const { salt, params, has_verifier } = await saltRes.json();

            // 2. Derive key
            await this.#crypto.deriveKey(password, salt, params);

            // 3. Verify or register
            if (has_verifier) {
                const verifierRes = await fetch('/vault/verifier', {
                    headers: { Accept: 'application/json' },
                });
                const { verifier } = await verifierRes.json();

                try {
                    const [ciphertextB64, ivB64] = verifier.split('.');
                    const decrypted = await this.#crypto.decrypt(ciphertextB64, ivB64);

                    if (decrypted !== VERIFIER_PLAINTEXT) {
                        throw new Error('Wrong password');
                    }
                } catch {
                    this.#crypto.lock();
                    this._showError('Wrong password. Try again.');
                    if (this.unlockBtn) this.unlockBtn.disabled = false;
                    return;
                }
            } else {
                // First time: create verifier
                const { ciphertext, iv } = await this.#crypto.encrypt(VERIFIER_PLAINTEXT);
                await fetch('/vault/verifier', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': getCsrfToken(),
                        Accept: 'application/json',
                    },
                    body: JSON.stringify({ verifier: `${ciphertext}.${iv}` }),
                });
            }

            // 4. Unlock
            this._hideModal();

            const queue = this.#resolveQueue.splice(0);
            queue.forEach((resolve) => resolve());

            document.dispatchEvent(new CustomEvent('vaultUnlocked'));
        } catch (err) {
            console.error('Vault unlock error:', err);
            this.#crypto.lock();
            this._showError('Failed to unlock vault. Check your password.');
        } finally {
            if (this.unlockBtn) this.unlockBtn.disabled = false;
        }
    }
}

// ===== VAULT DASHBOARD =====

class VaultDashboard {
    #crypto;
    #modal;
    #activeCard = null;

    constructor(cryptoInstance, modalInstance) {
        this.#crypto = cryptoInstance;
        this.#modal = modalInstance;
    }

    init() {
        document.querySelectorAll('.vault-reveal-btn').forEach((btn) => {
            btn.addEventListener('click', () => this._handleReveal(btn));
        });

        document.querySelectorAll('.vault-copy-btn').forEach((btn) => {
            btn.addEventListener('click', () => this._handleCopy(btn));
        });

        document.querySelectorAll('.vault-delete-btn').forEach((btn) => {
            btn.addEventListener('click', () => this._handleDelete(btn));
        });

        // Card click → detail modal
        document.querySelectorAll('.vault-card').forEach((card) => {
            card.addEventListener('click', (e) => {
                if (!e.target.closest('.vault-delete-btn') && !e.target.closest('.vault-copy-btn')) {
                    this._handleCardClick(card);
                }
            });
        });

        this._initDetailModal();
    }

    async _handleReveal(btn) {
        await this.#modal.demand();

        const card = btn.closest('.vault-card');
        if (!card) return;

        const encrypted = card.dataset.encrypted;
        const iv = card.dataset.iv;
        const dotsEl = card.querySelector('.vault-dots');
        if (!dotsEl) return;

        try {
            if (dotsEl.dataset.revealed === 'true') {
                dotsEl.textContent = '••••••••••••';
                dotsEl.dataset.revealed = 'false';
                btn.textContent = 'visibility';
            } else {
                const plaintext = await this.#crypto.decrypt(encrypted, iv);
                dotsEl.textContent = plaintext;
                dotsEl.dataset.revealed = 'true';
                btn.textContent = 'visibility_off';
            }
        } catch (err) {
            console.error('Reveal error:', err);
            vaultToast('Failed to decrypt password', 'error');
        }
    }

    async _handleCopy(btn) {
        await this.#modal.demand();

        const card = btn.closest('.vault-card');
        if (!card) return;

        const encrypted = card.dataset.encrypted;
        const iv = card.dataset.iv;

        try {
            const plaintext = await this.#crypto.decrypt(encrypted, iv);
            await navigator.clipboard.writeText(plaintext);
            vaultToast('Copied to clipboard!', 'success');

            const original = btn.textContent;
            btn.textContent = 'check';
            setTimeout(() => {
                btn.textContent = original;
            }, 2000);
        } catch (err) {
            console.error('Copy error:', err);
            vaultToast('Failed to copy password', 'error');
        }
    }

    async _handleDelete(btn) {
        const id = btn.dataset.id;
        if (!id) return;

        const card = btn.closest('.vault-card');
        const name = card?.querySelector('.card-title')?.textContent?.trim() || '';

        if (!await confirmDelete(name)) return;

        try {
            const res = await fetch(`/vault/entries/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': getCsrfToken(),
                    Accept: 'application/json',
                },
            });

            if (!res.ok) {
                vaultToast('Failed to delete entry', 'error');
                return;
            }

            if (card) card.remove();

            vaultToast('Entry deleted', 'info');
        } catch (err) {
            console.error('Delete error:', err);
            vaultToast('Error deleting entry', 'error');
        }
    }

    _handleCardClick(card) {
        this.#activeCard = card;

        const iconEl = document.getElementById('detailIcon');
        const titleEl = document.getElementById('detailTitle');
        const websiteEl = document.getElementById('detailWebsite');
        const emailEl = document.getElementById('detailEmail');
        const dotsEl = document.getElementById('detailPasswordDots');
        const revealBtn = document.getElementById('detailRevealBtn');

        if (iconEl) iconEl.textContent = card.dataset.icon || 'lock';
        if (titleEl) titleEl.textContent = card.dataset.nickname || card.dataset.website || 'Entry';
        if (websiteEl) websiteEl.textContent = card.dataset.website || '';
        if (emailEl) emailEl.textContent = card.dataset.email || '—';
        if (dotsEl) {
            dotsEl.textContent = '••••••••••••';
            delete dotsEl.dataset.revealed;
        }
        if (revealBtn) revealBtn.textContent = 'visibility';

        const notes = card.dataset.notes || '';
        const notesEl = document.getElementById('detailNotes');
        const notesField = document.getElementById('detailNotesField');
        if (notesEl) notesEl.textContent = notes || '—';
        if (notesField) notesField.style.display = notes ? '' : 'none';

        window.ModalSystem?.open('card-detail');
    }

    async _handleShare() {
        if (!this.#activeCard) return;
        await this.#modal.demand();

        const recipientEmail = document.getElementById('shareRecipientEmail')?.value.trim();
        const errEl = document.getElementById('shareError');
        if (errEl) errEl.style.display = 'none';

        if (!recipientEmail) {
            if (errEl) { errEl.textContent = 'Enter recipient email.'; errEl.style.display = ''; }
            return;
        }

        const submitBtn = document.getElementById('submitShareBtn');
        if (submitBtn) submitBtn.disabled = true;

        try {
            // 1. Decrypt original entry
            const plaintext = await this.#crypto.decrypt(
                this.#activeCard.dataset.encrypted,
                this.#activeCard.dataset.iv,
            );

            // 2. Generate random 256-bit share key
            const shareKeyBytes = crypto.getRandomValues(new Uint8Array(32));
            const shareKeyB64 = btoa(String.fromCharCode(...shareKeyBytes));

            // 3. Import as raw AES-GCM key
            const aesKey = await crypto.subtle.importKey(
                'raw', shareKeyBytes, { name: 'AES-GCM' }, false, ['encrypt'],
            );

            // 4. Encrypt plaintext with share key
            const iv = crypto.getRandomValues(new Uint8Array(12));
            const ciphertextBuf = await crypto.subtle.encrypt(
                { name: 'AES-GCM', iv }, aesKey, new TextEncoder().encode(plaintext),
            );
            const ciphertextB64 = btoa(String.fromCharCode(...new Uint8Array(ciphertextBuf)));
            const ivB64 = btoa(String.fromCharCode(...iv));

            // 5. SHA-256 hash of share key (server stores only the hash)
            const hashBuf = await crypto.subtle.digest('SHA-256', shareKeyBytes);
            const hashHex = Array.from(new Uint8Array(hashBuf))
                .map((b) => b.toString(16).padStart(2, '0'))
                .join('');

            // 6. POST to server
            const label = this.#activeCard.dataset.nickname
                || this.#activeCard.dataset.website
                || 'Shared Entry';

            const res = await fetch('/share/entries', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    Accept: 'application/json',
                },
                body: JSON.stringify({
                    recipient_email: recipientEmail,
                    label,
                    encrypted_payload: ciphertextB64,
                    iv: ivB64,
                    share_token_hash: hashHex,
                    share_key: shareKeyB64,
                }),
            });

            const data = await res.json();

            if (!res.ok) {
                const msg = data.errors?.recipient_email?.[0] ?? data.message ?? 'Failed to create share.';
                if (errEl) { errEl.textContent = msg; errEl.style.display = ''; }
                return;
            }

            // 7. Show confirmation — email was dispatched server-side
            const confirmEmailEl = document.getElementById('shareConfirmEmail');
            if (confirmEmailEl) confirmEmailEl.textContent = recipientEmail;
            document.getElementById('shareStep1').style.display = 'none';
            document.getElementById('shareStep2').style.display = '';
        } catch (err) {
            console.error('Share error:', err);
            vaultToast('Failed to create share.', 'error');
        } finally {
            if (submitBtn) submitBtn.disabled = false;
        }
    }

    _initDetailModal() {
        const revealBtn = document.getElementById('detailRevealBtn');
        const copyBtn = document.getElementById('detailCopyBtn');
        const deleteBtn = document.getElementById('detailDeleteBtn');
        const editBtn = document.getElementById('detailEditBtn');
        const shareBtn = document.getElementById('detailShareBtn');
        const submitShareBtn = document.getElementById('submitShareBtn');

        if (!revealBtn && !copyBtn && !deleteBtn) return;

        editBtn?.addEventListener('click', () => {
            if (!this.#activeCard) return;
            VaultEdit.open(this.#activeCard);
        });

        revealBtn?.addEventListener('click', async () => {
            if (!this.#activeCard) return;
            await this.#modal.demand();

            const dotsEl = document.getElementById('detailPasswordDots');
            if (!dotsEl) return;

            if (dotsEl.dataset.revealed === 'true') {
                dotsEl.textContent = '••••••••••••';
                dotsEl.dataset.revealed = 'false';
                if (revealBtn) revealBtn.textContent = 'visibility';
            } else {
                try {
                    const pw = await this.#crypto.decrypt(
                        this.#activeCard.dataset.encrypted,
                        this.#activeCard.dataset.iv,
                    );
                    dotsEl.textContent = pw;
                    dotsEl.dataset.revealed = 'true';
                    if (revealBtn) revealBtn.textContent = 'visibility_off';
                } catch {
                    vaultToast('Failed to decrypt', 'error');
                }
            }
        });

        copyBtn?.addEventListener('click', async () => {
            if (!this.#activeCard) return;
            await this.#modal.demand();

            try {
                const pw = await this.#crypto.decrypt(
                    this.#activeCard.dataset.encrypted,
                    this.#activeCard.dataset.iv,
                );
                await navigator.clipboard.writeText(pw);
                vaultToast('Copied!', 'success');
                const orig = copyBtn.textContent;
                copyBtn.textContent = 'check';
                setTimeout(() => {
                    copyBtn.textContent = orig;
                }, 2000);
            } catch {
                vaultToast('Failed to copy', 'error');
            }
        });

        deleteBtn?.addEventListener('click', async () => {
            if (!this.#activeCard) return;

            const name = this.#activeCard.querySelector('.card-title')?.textContent?.trim()
                || this.#activeCard.dataset.nickname
                || '';

            window.ModalSystem?.close('card-detail');
            if (!await confirmDelete(name)) return;

            const id = this.#activeCard.dataset.entryId;
            try {
                const res = await fetch(`/vault/entries/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': getCsrfToken(),
                        Accept: 'application/json',
                    },
                });

                if (res.ok) {
                    this.#activeCard.remove();
                    this.#activeCard = null;
                    window.ModalSystem?.close('card-detail');
                    vaultToast('Entry deleted', 'info');

                    if (!document.querySelector('.vault-card')) {
                        const grid = document.getElementById('vaultGrid');
                        if (grid) {
                            grid.innerHTML = '<p class="vault-empty-msg">No saved passwords yet. <a href="/generator">Generate one →</a></p>';
                        }
                    }
                }
            } catch {
                vaultToast('Error deleting entry', 'error');
            }
        });

        shareBtn?.addEventListener('click', () => {
            if (!this.#activeCard) return;
            // Reset share modal state
            document.getElementById('shareStep1').style.display = '';
            document.getElementById('shareStep2').style.display = 'none';
            const emailInput = document.getElementById('shareRecipientEmail');
            if (emailInput) emailInput.value = '';
            const errEl = document.getElementById('shareError');
            if (errEl) errEl.style.display = 'none';

            window.ModalSystem?.close('card-detail');
            window.ModalSystem?.open('share-entry');
        });

        submitShareBtn?.addEventListener('click', () => this._handleShare());
    }
}

// ===== VAULT EDIT =====

const VaultEdit = {
    _card: null,

    open(card) {
        this._card = card;
        this._reset();

        document.getElementById('editNickname').value = card.dataset.nickname || '';
        document.getElementById('editWebsite').value = card.dataset.website || '';
        document.getElementById('editEmailHint').value = card.dataset.email || '';
        document.getElementById('editNotes').value = card.dataset.notes || '';

        window.ModalSystem?.close('card-detail');
        window.ModalSystem?.open('edit-entry');
        setTimeout(() => document.getElementById('editNickname').focus(), 100);
    },

    _reset() {
        document.getElementById('editFormWrap').style.display = '';
        document.getElementById('editSuccessWrap').style.display = 'none';
        document.getElementById('editPasswordInputWrap').style.display = 'none';
        document.getElementById('editPasswordPlaceholder').style.display = '';
        document.getElementById('editTogglePasswordBtn').textContent = 'CHANGE';
        document.getElementById('editNewPassword').value = '';
        document.getElementById('editPasswordError').style.display = 'none';
        document.getElementById('editGenericError').style.display = 'none';
        const btn = document.getElementById('editSaveBtn');
        if (btn) { btn.disabled = false; btn.textContent = 'SAVE CHANGES'; }
    },

    togglePasswordChange() {
        const wrap = document.getElementById('editPasswordInputWrap');
        const placeholder = document.getElementById('editPasswordPlaceholder');
        const toggleBtn = document.getElementById('editTogglePasswordBtn');

        if (wrap.style.display === 'none') {
            wrap.style.display = '';
            placeholder.style.display = 'none';
            toggleBtn.textContent = 'KEEP CURRENT';
            document.getElementById('editNewPassword').focus();
        } else {
            wrap.style.display = 'none';
            placeholder.style.display = '';
            toggleBtn.textContent = 'CHANGE';
            document.getElementById('editNewPassword').value = '';
            document.getElementById('editPasswordError').style.display = 'none';
        }
    },

    cancel() {
        window.ModalSystem?.close('edit-entry');
        if (this._card) {
            // Re-open detail modal showing current (unchanged) data
            const card = this._card;
            const iconEl = document.getElementById('detailIcon');
            const titleEl = document.getElementById('detailTitle');
            const websiteEl = document.getElementById('detailWebsite');
            const emailEl = document.getElementById('detailEmail');
            const notesEl = document.getElementById('detailNotes');
            const notesField = document.getElementById('detailNotesField');
            const dotsEl = document.getElementById('detailPasswordDots');
            const revealBtn = document.getElementById('detailRevealBtn');

            if (iconEl) iconEl.textContent = card.dataset.icon || 'lock';
            if (titleEl) titleEl.textContent = card.dataset.nickname || card.dataset.website || 'Entry';
            if (websiteEl) websiteEl.textContent = card.dataset.website || '';
            if (emailEl) emailEl.textContent = card.dataset.email || '—';
            const notes = card.dataset.notes || '';
            if (notesEl) notesEl.textContent = notes || '—';
            if (notesField) notesField.style.display = notes ? '' : 'none';
            if (dotsEl) { dotsEl.textContent = '••••••••••••'; delete dotsEl.dataset.revealed; }
            if (revealBtn) revealBtn.textContent = 'visibility';

            window.ModalSystem?.open('card-detail');
        }
    },

    done() {
        window.ModalSystem?.close('edit-entry');
        if (this._card) {
            const card = this._card;
            const iconEl = document.getElementById('detailIcon');
            const titleEl = document.getElementById('detailTitle');
            const websiteEl = document.getElementById('detailWebsite');
            const emailEl = document.getElementById('detailEmail');
            const notesEl = document.getElementById('detailNotes');
            const notesField = document.getElementById('detailNotesField');
            const dotsEl = document.getElementById('detailPasswordDots');
            const revealBtn = document.getElementById('detailRevealBtn');

            if (iconEl) iconEl.textContent = card.dataset.icon || 'lock';
            if (titleEl) titleEl.textContent = card.dataset.nickname || card.dataset.website || 'Entry';
            if (websiteEl) websiteEl.textContent = card.dataset.website || '';
            if (emailEl) emailEl.textContent = card.dataset.email || '—';
            const notes = card.dataset.notes || '';
            if (notesEl) notesEl.textContent = notes || '—';
            if (notesField) notesField.style.display = notes ? '' : 'none';
            if (dotsEl) { dotsEl.textContent = '••••••••••••'; delete dotsEl.dataset.revealed; }
            if (revealBtn) revealBtn.textContent = 'visibility';

            window.ModalSystem?.open('card-detail');
        }
    },

    async save() {
        const nickname = document.getElementById('editNickname').value.trim();
        const website = document.getElementById('editWebsite').value.trim();
        const emailHint = document.getElementById('editEmailHint').value.trim();
        const notes = document.getElementById('editNotes').value.trim();

        const changingPassword = document.getElementById('editPasswordInputWrap').style.display !== 'none';
        const newPassword = document.getElementById('editNewPassword').value;

        document.getElementById('editPasswordError').style.display = 'none';
        document.getElementById('editGenericError').style.display = 'none';

        if (changingPassword && !newPassword) {
            const errEl = document.getElementById('editPasswordError');
            errEl.textContent = 'Enter a new password or keep the current one.';
            errEl.style.display = '';
            return;
        }

        const btn = document.getElementById('editSaveBtn');
        btn.disabled = true;
        btn.textContent = 'SAVING…';

        const body = { nickname, website, email_hint: emailHint, notes };

        if (changingPassword) {
            try {
                await window.vaultModal.demand();
                const { ciphertext, iv } = await window.vaultCrypto.encrypt(newPassword);
                body.encrypted_password = ciphertext;
                body.iv = iv;
            } catch (err) {
                console.error('Encryption error:', err);
                btn.disabled = false;
                btn.textContent = 'SAVE CHANGES';
                const errEl = document.getElementById('editGenericError');
                errEl.textContent = 'Failed to encrypt. Make sure your vault is unlocked.';
                errEl.style.display = '';
                return;
            }
        }

        const entryId = this._card?.dataset.entryId;

        try {
            const res = await fetch(`/vault/entries/${entryId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    Accept: 'application/json',
                },
                body: JSON.stringify(body),
            });

            btn.disabled = false;
            btn.textContent = 'SAVE CHANGES';

            if (!res.ok) {
                const data = await res.json().catch(() => ({}));
                const errEl = document.getElementById('editGenericError');
                errEl.textContent = data.message || 'Failed to save changes. Please try again.';
                errEl.style.display = '';
                return;
            }

            const { entry } = await res.json();

            // Patch card dataset so all subsequent operations use fresh data
            if (this._card) {
                this._card.dataset.nickname = entry.nickname   ?? '';
                this._card.dataset.website  = entry.website    ?? '';
                this._card.dataset.email    = entry.email_hint ?? '';
                this._card.dataset.notes    = entry.notes      ?? '';
                if (body.encrypted_password) {
                    this._card.dataset.encrypted = body.encrypted_password;
                    this._card.dataset.iv        = body.iv;
                }

                // Patch visible card DOM
                const titleEl = this._card.querySelector('h3');
                if (titleEl) {
                    titleEl.textContent = (entry.nickname || entry.website || 'ENTRY').toUpperCase();
                }
                const emailEl = this._card.querySelector('.email');
                if (emailEl) emailEl.textContent = entry.email_hint || '';
            }

            document.getElementById('editFormWrap').style.display = 'none';
            document.getElementById('editSuccessWrap').style.display = '';

        } catch {
            btn.disabled = false;
            btn.textContent = 'SAVE CHANGES';
            const errEl = document.getElementById('editGenericError');
            errEl.textContent = 'Network error. Please try again.';
            errEl.style.display = '';
        }
    },
};

// ===== MODULE INIT =====

window.vaultCrypto = new VaultCrypto();
window.VaultEdit = VaultEdit;

document.addEventListener('DOMContentLoaded', () => {
    const modal = new VaultModal(window.vaultCrypto);
    window.vaultModal = modal;

    const dashboard = new VaultDashboard(window.vaultCrypto, modal);
    dashboard.init();

    // Lock vault on logout
    const logoutForm = document.querySelector('form[action*="logout"]');
    if (logoutForm) {
        logoutForm.addEventListener('submit', () => {
            window.vaultCrypto.lock();
        });
    }
});
