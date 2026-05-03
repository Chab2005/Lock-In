<div class="card">
    <div class="save-header">
        <h3>SAVE TO VAULT</h3>
        <button type="button" id="iconPickerBtn" class="icon-picker-btn" title="Choose icon">
            <span class="material-symbols-outlined" id="selectedIconDisplay">lock</span>
            <span>ICON</span>
        </button>
    </div>
    <input type="hidden" id="selectedIconInput" value="lock">

    <div class="form-grid">
        <input id="saveService" placeholder="EX: GITHUB, BANK..." autocomplete="off">
        <input id="saveUrl" placeholder="HTTPS://..." autocomplete="off">
        <input id="saveEmail" placeholder="NAME@EXAMPLE.COM" autocomplete="off">
        <input id="savePassword" placeholder="Generated password will appear here" autocomplete="off" readonly>
    </div>

    <button id="saveVaultBtn" class="btn-main" disabled>
        Save to vault
    </button>

    <div class="save-info">
        <small>Your settings are saved locally on this device</small>
    </div>
</div>
