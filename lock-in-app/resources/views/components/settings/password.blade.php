<div class="settings-section">
    <div class="settings-card">
        <div class="card-item">
            <div class="item-info">
                <div class="item-icon">
                    <span class="material-symbols-outlined">lock_reset</span>
                </div>
                <div class="item-text">
                    <h4>Want to Change Your Password?</h4>
                    <p>Update the master password used to access your account.</p>
                </div>
            </div>
            <div class="action-group">
                <button class="btn-action" onclick="PasswordChange.open()">CHANGE</button>
            </div>
        </div>
    </div>
</div>

<x-ui.modal id="change-password" title="CHANGE PASSWORD">
    <div id="pwFormWrap">
        <div class="form-group" style="margin-bottom: 20px;">
            <label for="pwCurrent"
                   style="font-size: 11px; color: var(--color-text-secondary);
                          letter-spacing: 0.1em; text-transform: uppercase;
                          display: block; margin-bottom: 8px;">
                CURRENT PASSWORD
            </label>
            <input type="password" id="pwCurrent" placeholder="••••••••••••"
                   autocomplete="current-password"
                   style="width: 100%; background: transparent; border: none;
                          border-bottom: 1px solid var(--color-border); padding: 10px 0;
                          color: var(--color-text-primary); font-family: inherit; font-size: 16px;">
            <p id="pwCurrentError"
               style="color: #ff3d3d; font-size: 12px; margin-top: 4px; display: none;"></p>
        </div>

        <div class="form-group" style="margin-bottom: 20px;">
            <label for="pwNew"
                   style="font-size: 11px; color: var(--color-text-secondary);
                          letter-spacing: 0.1em; text-transform: uppercase;
                          display: block; margin-bottom: 8px;">
                NEW PASSWORD
            </label>
            <input type="password" id="pwNew" placeholder="••••••••••••"
                   autocomplete="new-password"
                   style="width: 100%; background: transparent; border: none;
                          border-bottom: 1px solid var(--color-border); padding: 10px 0;
                          color: var(--color-text-primary); font-family: inherit; font-size: 16px;">
            <p id="pwNewError"
               style="color: #ff3d3d; font-size: 12px; margin-top: 4px; display: none;"></p>
        </div>

        <div class="form-group" style="margin-bottom: 8px;">
            <label for="pwConfirm"
                   style="font-size: 11px; color: var(--color-text-secondary);
                          letter-spacing: 0.1em; text-transform: uppercase;
                          display: block; margin-bottom: 8px;">
                CONFIRM NEW PASSWORD
            </label>
            <input type="password" id="pwConfirm" placeholder="••••••••••••"
                   autocomplete="new-password"
                   style="width: 100%; background: transparent; border: none;
                          border-bottom: 1px solid var(--color-border); padding: 10px 0;
                          color: var(--color-text-primary); font-family: inherit; font-size: 16px;">
            <p id="pwConfirmError"
               style="color: #ff3d3d; font-size: 12px; margin-top: 4px; display: none;"></p>
        </div>

        <p id="pwGenericError"
           style="color: #ff3d3d; font-size: 13px; display: none; margin: 12px 0 0;"></p>

        <div style="display: flex; gap: 12px; margin-top: 24px;">
            <button type="button" class="btn-secondary" onclick="PasswordChange.cancel()">CANCEL</button>
            <button type="button" class="btn-primary" id="pwSubmitBtn"
                    style="flex: 1;" onclick="PasswordChange.submit()">UPDATE PASSWORD</button>
        </div>
    </div>

    <div id="pwSuccessWrap" style="display: none; text-align: center; padding: 8px 0;">
        <span class="material-symbols-outlined" style="font-size: 48px; color: #4cd137;">check_circle</span>
        <h3 style="font-family: 'Space Grotesk'; margin-top: 8px;">PASSWORD UPDATED</h3>
        <p style="color: var(--color-text-secondary); font-size: 13px; margin: 8px 0 24px;">
            Your password has been changed successfully.
        </p>
        <button type="button" class="btn-primary" style="width: 100%;"
                onclick="window.ModalSystem?.close('change-password')">DONE</button>
    </div>
</x-ui.modal>

@push('scripts')
<script>
const PasswordChange = {
    open() {
        document.getElementById('pwFormWrap').style.display = '';
        document.getElementById('pwSuccessWrap').style.display = 'none';
        document.getElementById('pwCurrent').value = '';
        document.getElementById('pwNew').value = '';
        document.getElementById('pwConfirm').value = '';
        this._clearErrors();
        window.ModalSystem?.open('change-password');
        setTimeout(() => document.getElementById('pwCurrent').focus(), 100);
    },

    cancel() {
        window.ModalSystem?.close('change-password');
    },

    async submit() {
        const current = document.getElementById('pwCurrent').value;
        const password = document.getElementById('pwNew').value;
        const confirm = document.getElementById('pwConfirm').value;

        this._clearErrors();

        let hasError = false;
        if (!current) { this._fieldError('pwCurrent', 'Current password is required.'); hasError = true; }
        if (!password) { this._fieldError('pwNew', 'New password is required.'); hasError = true; }
        if (!confirm)  { this._fieldError('pwConfirm', 'Please confirm your new password.'); hasError = true; }
        if (hasError) return;

        if (password !== confirm) {
            this._fieldError('pwConfirm', 'Passwords do not match.');
            return;
        }

        const btn = document.getElementById('pwSubmitBtn');
        btn.disabled = true;
        btn.textContent = 'UPDATING…';

        const res = await fetch('/setings/password', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ current_password: current, password, password_confirmation: confirm }),
        });

        btn.disabled = false;
        btn.textContent = 'UPDATE PASSWORD';

        if (res.ok) {
            document.getElementById('pwFormWrap').style.display = 'none';
            document.getElementById('pwSuccessWrap').style.display = '';
            document.getElementById('pwCurrent').value = '';
            document.getElementById('pwNew').value = '';
            document.getElementById('pwConfirm').value = '';
            return;
        }

        const data = await res.json().catch(() => ({}));

        if (res.status === 422 && data.errors) {
            if (data.errors.current_password) this._fieldError('pwCurrent', data.errors.current_password[0]);
            if (data.errors.password)          this._fieldError('pwNew', data.errors.password[0]);
        } else {
            const err = document.getElementById('pwGenericError');
            err.textContent = data.message || 'Failed to update password. Please try again.';
            err.style.display = '';
        }
    },

    _fieldError(id, message) {
        const el = document.getElementById(id + 'Error');
        if (el) { el.textContent = message; el.style.display = ''; }
    },

    _clearErrors() {
        ['pwCurrent', 'pwNew', 'pwConfirm'].forEach(id => {
            const el = document.getElementById(id + 'Error');
            if (el) { el.textContent = ''; el.style.display = 'none'; }
        });
        const gen = document.getElementById('pwGenericError');
        if (gen) { gen.textContent = ''; gen.style.display = 'none'; }
    },
};

</script>
@endpush
