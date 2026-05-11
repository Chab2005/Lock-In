@push('styles')
    @vite(['resources/js/webauthn-settings.ts'])
@endpush

<div class="settings-section" id="passkeysSection">
    <div class="section-header-flex">
        <h2 class="section-title">Passkeys</h2>
        <button class="btn-action btn-filled" id="addPasskeyBtn" onclick="Passkeys.register()">
            CREATE PASSKEY
        </button>
    </div>

    <div class="settings-card" id="passkeyList">
        <div class="card-item" style="color: var(--color-text-secondary); font-size: 13px;">
            Loading…
        </div>
    </div>

    <p id="passkeyError" style="color:#ff3d3d; font-size:13px; margin-top:8px; display:none;"></p>
</div>

{{-- Register passkey name modal --}}
<x-ui.modal id="passkey-name" title="NAME YOUR PASSKEY">
    <p style="color: var(--color-text-secondary); font-size: 13px; margin-bottom: 16px;">
        Give this passkey a name so you can identify it later (e.g. "MacBook Touch ID").
    </p>
    <div class="form-group">
        <label for="passkeyAlias">PASSKEY NAME</label>
        <input type="text" id="passkeyAlias" placeholder="e.g. MacBook Touch ID"
               maxlength="60" autocomplete="off"
               style="width:100%; background:transparent; border:none;
                      border-bottom:1px solid var(--color-border); padding:10px 0;
                      color:var(--color-text-primary); font-family:inherit; font-size:16px;">
    </div>
    <p id="passkeyNameError" style="color:#ff3d3d; font-size:13px; display:none; margin-top:8px;"></p>
    <div style="display:flex; gap:12px; margin-top:20px;">
        <button class="btn-secondary" onclick="window.ModalSystem?.close('passkey-name')">CANCEL</button>
        <button class="btn-primary" style="flex:1;" onclick="Passkeys.confirmRegister()">
            SAVE &amp; ACTIVATE
        </button>
    </div>
</x-ui.modal>
