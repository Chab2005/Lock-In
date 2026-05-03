<div id="vaultUnlockModal" class="vault-modal" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="vaultModalTitle">
    <div class="vault-modal-inner">
        <div class="vault-modal-header">
            <span class="material-symbols-outlined">lock</span>
            <h2 id="vaultModalTitle">UNLOCK VAULT</h2>
        </div>
        <p class="vault-modal-subtitle">Enter your login password to decrypt your vault.</p>
        <div class="vault-modal-field">
            <label for="vaultUnlockPassword">PASSWORD</label>
            <input type="password" id="vaultUnlockPassword" placeholder="••••••••••••" autocomplete="current-password">
        </div>
        <p id="vaultUnlockError" class="vault-modal-error" style="display:none;"></p>
        <button id="vaultUnlockBtn" class="vault-unlock-submit">UNLOCK <span class="material-symbols-outlined">lock_open</span></button>
    </div>
</div>
