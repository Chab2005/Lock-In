@php
    $twoFactorEnabled      = auth()->user()->hasTwoFactorEnabled();
    $emailOtpEnabled       = auth()->user()->hasEmailTwoFactorEnabled();
@endphp

<div class="settings-section">
    <h2 class="section-title">Authentication</h2>

    <div class="settings-card">
        <div class="card-item">
            <div class="item-info">
                <div class="item-icon">
                    <span class="material-symbols-outlined">mobile</span>
                </div>
                <div class="item-text">
                    <h4>Authentication App</h4>
                    <p>TOTP (Google Authenticator, Authy, etc.)</p>
                </div>
            </div>
            <div class="action-group">
                @if ($twoFactorEnabled)
                    <span class="activeBadge">ACTIVE</span>
                    <button class="btn-action" onclick="window.ModalSystem?.open('totp-manage')">MANAGE</button>
                @else
                    <button class="btn-action" id="enable2faBtn" onclick="TotpSetup.start()">ENABLE</button>
                @endif
            </div>
        </div>
        <div class="card-item">
            <div class="item-info">
                <div class="item-icon">
                    <span class="material-symbols-outlined">mail</span>
                </div>
                <div class="item-text">
                    <h4>Email OTP</h4>
                    <p>6-digit code sent to your email on each login</p>
                    @if ($emailOtpEnabled && !$twoFactorEnabled)
                        <p style="font-size: 11px; color: var(--color-text-secondary); margin-top: 4px;">
                            Set up an authenticator app to be able to disable this.
                        </p>
                    @endif
                </div>
            </div>
            <div class="action-group">
                @if ($emailOtpEnabled)
                    <span class="activeBadge">ACTIVE</span>
                    <button class="btn-action" onclick="EmailOtp.toggle(false)">DISABLE</button>
                @else
                    <button class="btn-action" onclick="EmailOtp.toggle(true)">ENABLE</button>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- TOTP Setup Modal --}}
<x-ui.modal id="totp-setup" title="SETUP AUTHENTICATOR">
    <div id="totpStep1">
        <p style="color: var(--color-text-secondary); font-size: 13px; margin-bottom: 16px; line-height: 1.6;">
            Scan with your authenticator app, then enter the 6-digit code to confirm.
        </p>

        <div id="totpQrCode"
             style="display: flex; justify-content: center; align-items: center;
                    min-height: 200px; margin: 16px 0; background: #fff; padding: 12px;">
            <span style="color: #999;">Loading QR code…</span>
        </div>

        <div style="margin-bottom: 20px;">
            <label style="font-size: 11px; color: var(--color-text-secondary);
                          letter-spacing: 0.1em; text-transform: uppercase;">MANUAL ENTRY KEY</label>
            <div style="display: flex; align-items: center; gap: 10px; margin-top: 8px;">
                <code id="totpSecretKey"
                      style="font-size: 13px; letter-spacing: 0.12em; word-break: break-all;
                             color: var(--color-text-primary); flex: 1;">—</code>
                <button type="button" onclick="TotpSetup.copyKey()"
                        title="Copy key"
                        style="background: none; border: none; color: var(--color-text-secondary);
                               cursor: pointer; padding: 4px;">
                    <span class="material-symbols-outlined" style="font-size: 18px;">content_copy</span>
                </button>
            </div>
        </div>

        <div style="margin-bottom: 8px;">
            <label for="totpConfirmCode"
                   style="font-size: 11px; color: var(--color-text-secondary);
                          letter-spacing: 0.1em; text-transform: uppercase; display: block; margin-bottom: 8px;">
                VERIFICATION CODE
            </label>
            <input type="text" id="totpConfirmCode"
                   inputmode="numeric" maxlength="6" pattern="[0-9]{6}"
                   placeholder="000000" autocomplete="one-time-code"
                   style="width: 100%; background: transparent; border: none;
                          border-bottom: 1px solid var(--color-border); padding: 10px 0;
                          color: var(--color-text-primary); font-family: inherit; font-size: 20px;
                          letter-spacing: 0.3em;">
        </div>

        <p id="totpSetupError"
           style="color: #ff3d3d; font-size: 13px; display: none; margin: 8px 0;"></p>

        <div style="display: flex; gap: 12px; margin-top: 20px;">
            <button type="button" class="btn-secondary"
                    onclick="TotpSetup.cancel()">CANCEL</button>
            <button type="button" class="btn-primary"
                    style="flex: 1;" onclick="TotpSetup.confirm()">CONFIRM SETUP</button>
        </div>
    </div>

    <div id="totpStep2" style="display: none;">
        <div style="text-align: center; margin-bottom: 20px;">
            <span class="material-symbols-outlined" style="font-size: 48px; color: #4cd137;">check_circle</span>
            <h3 style="font-family: 'Space Grotesk'; margin-top: 8px;">2FA ENABLED</h3>
        </div>
        <p style="color: var(--color-text-secondary); font-size: 13px; margin-bottom: 16px; line-height: 1.6;">
            Save these recovery codes somewhere safe. Each code can only be used once.
        </p>
        <div id="totpRecoveryCodes"
             style="background: var(--color-surface-hover); padding: 16px;
                    font-family: 'Space Grotesk'; font-size: 13px;
                    line-height: 2; letter-spacing: 0.05em; word-break: break-all;"></div>
        <button type="button" class="btn-primary"
                style="width: 100%; margin-top: 20px;"
                onclick="window.location.reload()">DONE</button>
    </div>
</x-ui.modal>

{{-- TOTP Manage Modal --}}
<x-ui.modal id="totp-manage" title="MANAGE 2FA">
    <p style="color: var(--color-text-secondary); font-size: 13px; margin-bottom: 20px;">
        Two-factor authentication is active on your account.
    </p>

    <div style="display: flex; flex-direction: column; gap: 12px;">
        <button type="button" class="btn-secondary" onclick="TotpSetup.showRecoveryCodes()">
            <span class="material-symbols-outlined">key</span>&nbsp; VIEW RECOVERY CODES
        </button>
        <button type="button" onclick="TotpSetup.disable()"
                style="display: flex; align-items: center; justify-content: center; gap: 8px;
                       background: transparent; border: 1px solid #ff3d3d; color: #ff3d3d;
                       padding: 14px 24px; cursor: pointer; font-family: 'Space Grotesk';
                       font-size: 12px; letter-spacing: 0.15em; text-transform: uppercase;">
            <span class="material-symbols-outlined">no_accounts</span> DISABLE 2FA
        </button>
    </div>

    <div id="recoveryCodesList" style="display: none; margin-top: 20px;">
        <label style="font-size: 11px; color: var(--color-text-secondary);
                      letter-spacing: 0.1em; text-transform: uppercase;">YOUR RECOVERY CODES</label>
        <div id="recoveryCodesContent"
             style="background: var(--color-surface-hover); padding: 16px; margin-top: 8px;
                    font-family: 'Space Grotesk'; font-size: 13px; line-height: 2;
                    letter-spacing: 0.05em; word-break: break-all;"></div>
    </div>
</x-ui.modal>

@push('scripts')
<script>
const TotpSetup = {
    async start() {
        const btn = document.getElementById('enable2faBtn');
        if (btn) btn.disabled = true;

        try {
            const res = await fetch('/user/two-factor-authentication', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    'Accept': 'application/json',
                },
            });
            if (!res.ok) throw new Error('Failed to initialise 2FA');
        } catch {
            alert('Could not enable 2FA. Please try again.');
            if (btn) btn.disabled = false;
            return;
        }

        window.ModalSystem?.open('totp-setup');
        document.getElementById('totpStep1').style.display = '';
        document.getElementById('totpStep2').style.display = 'none';
        document.getElementById('totpSetupError').style.display = 'none';
        document.getElementById('totpConfirmCode').value = '';

        const [qrRes, keyRes] = await Promise.all([
            fetch('/user/two-factor-qr-code', { headers: { Accept: 'application/json' } }),
            fetch('/user/two-factor-secret-key', { headers: { Accept: 'application/json' } }),
        ]);

        const { svg } = await qrRes.json();
        const { secretKey } = await keyRes.json();

        document.getElementById('totpQrCode').innerHTML = svg;
        document.getElementById('totpSecretKey').textContent = secretKey;
        document.getElementById('totpConfirmCode').focus();

        if (btn) btn.disabled = false;
    },

    async confirm() {
        const code = document.getElementById('totpConfirmCode').value.trim();
        const errEl = document.getElementById('totpSetupError');

        if (code.length !== 6 || !/^\d{6}$/.test(code)) {
            errEl.textContent = 'Enter the 6-digit code from your app.';
            errEl.style.display = '';
            return;
        }

        const res = await fetch('/user/confirmed-two-factor-authentication', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ code }),
        });

        if (!res.ok) {
            errEl.textContent = 'Invalid code. Try again.';
            errEl.style.display = '';
            document.getElementById('totpConfirmCode').value = '';
            document.getElementById('totpConfirmCode').focus();
            return;
        }

        errEl.style.display = 'none';

        const codesRes = await fetch('/user/two-factor-recovery-codes', {
            headers: { Accept: 'application/json' },
        });
        const codes = await codesRes.json();

        document.getElementById('totpRecoveryCodes').innerHTML =
            codes.map(c => `<div>${c}</div>`).join('');
        document.getElementById('totpStep1').style.display = 'none';
        document.getElementById('totpStep2').style.display = '';
    },

    async cancel() {
        // Disable 2FA if it was enabled mid-setup (no code confirmed yet)
        await fetch('/user/two-factor-authentication', {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                'Accept': 'application/json',
            },
        });
        window.ModalSystem?.close('totp-setup');
    },

    copyKey() {
        const key = document.getElementById('totpSecretKey').textContent;
        navigator.clipboard.writeText(key).catch(() => {});
    },

    async showRecoveryCodes() {
        const res = await fetch('/user/two-factor-recovery-codes', {
            headers: { Accept: 'application/json' },
        });
        const codes = await res.json();

        document.getElementById('recoveryCodesContent').innerHTML =
            codes.map(c => `<div>${c}</div>`).join('');
        document.getElementById('recoveryCodesList').style.display = '';
    },

    async disable() {
        if (!confirm('Disable two-factor authentication? Your account will be less secure.')) return;

        const res = await fetch('/user/two-factor-authentication', {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                'Accept': 'application/json',
            },
        });

        if (res.ok) {
            window.location.reload();
        } else {
            const data = await res.json().catch(() => ({}));
            alert(data.message || 'Failed to disable 2FA. Please try again.');
        }
    },
};

const EmailOtp = {
    async toggle(enabled) {
        const res = await fetch('/2fa/email/toggle', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                Accept: 'application/json',
            },
            body: JSON.stringify({ enabled }),
        });

        if (res.ok) {
            window.location.reload();
        } else {
            const data = await res.json().catch(() => ({}));
            alert(data.message || 'Failed to update email OTP. Please try again.');
        }
    },
};
</script>
@endpush
