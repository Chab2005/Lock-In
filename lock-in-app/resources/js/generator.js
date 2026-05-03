// Password Generator Module
class PasswordGenerator {
    constructor() {
        this.currentPassword = null;
        this.currentMetadata = null;
        this.init();
    }

    init() {
        this.cacheElements();
        this.bindEvents();
        this.loadPreferences();
    }

    cacheElements() {
        // Length slider
        this.lengthSlider = document.getElementById('passwordLength');
        this.lengthValue = document.getElementById('lengthValue');

        // Charset checkboxes
        this.charUppercase = document.getElementById('charUppercase');
        this.charLowercase = document.getElementById('charLowercase');
        this.charNumbers = document.getElementById('charNumbers');
        this.charSymbols = document.getElementById('charSymbols');
        this.excludeAmbiguous = document.getElementById('excludeAmbiguous');

        // Output elements
        this.generatedPassword = document.getElementById('generatedPassword');
        this.generateBtn = document.getElementById('generateBtn');
        this.generateBtnIcon = document.getElementById('generateBtn')?.querySelector('.gen-btn-icon');
        this.generateBtnText = document.getElementById('generateBtn')?.querySelector('.gen-btn-text');
        this.copyBtn = document.getElementById('copyBtn');

        // Icon picker elements
        this.iconPickerBtn = document.getElementById('iconPickerBtn');
        this.selectedIconDisplay = document.getElementById('selectedIconDisplay');
        this.selectedIconInput = document.getElementById('selectedIconInput');

        // Entropy/Strength elements
        this.strengthBadge = document.getElementById('strengthBadge');
        this.strengthFill = document.getElementById('strengthFill');
        this.entropyValue = document.getElementById('entropyValue');
        this.combinationsValue = document.getElementById('combinationsValue');

        // Analysis elements
        this.analysisLength = document.getElementById('analysisLength');
        this.analysisPools = document.getElementById('analysisPools');
        this.analysisCombinations = document.getElementById('analysisCombinations');
        this.analysisCrackTime = document.getElementById('analysisCrackTime');

        // Save elements
        this.saveService = document.getElementById('saveService');
        this.saveUrl = document.getElementById('saveUrl');
        this.saveEmail = document.getElementById('saveEmail');
        this.savePassword = document.getElementById('savePassword');
        this.saveVaultBtn = document.getElementById('saveVaultBtn');
    }

    bindEvents() {
        this.lengthSlider.addEventListener('input', () => this.updateLength());

        const charsetBoxes = [this.charUppercase, this.charLowercase, this.charNumbers, this.charSymbols];
        charsetBoxes.forEach(cb => cb.addEventListener('change', () => {
            this._enforceMinOneCharset(charsetBoxes);
            this.savePreferences();
        }));
        // excludeAmbiguous does not participate in the min-one rule
        this.excludeAmbiguous.addEventListener('change', () => this.savePreferences());

        this.generateBtn.addEventListener('click', () => this.generate());
        this.copyBtn.addEventListener('click', () => this.copyToClipboard());
        this.saveVaultBtn.addEventListener('click', () => this.saveToVault());

        // Icon picker
        this.iconPickerBtn?.addEventListener('click', () => window.ModalSystem?.open('icon-picker'));
        document.querySelectorAll('.icon-option').forEach(btn => {
            btn.addEventListener('click', () => this._selectIcon(btn.dataset.icon));
        });
    }

    updateLength() {
        this.lengthValue.textContent = this.lengthSlider.value;
        this.updateSliderBackground();
        this.savePreferences();
        if (this.currentPassword) {
            this.generate();
        }
    }

    updateSliderBackground() {
        const min = this.lengthSlider.min;
        const max = this.lengthSlider.max;
        const val = this.lengthSlider.value;
        const percent = ((val - min) / (max - min)) * 100;

        this.lengthSlider.style.background = `linear-gradient(
            to right,
            #ffffff 0%,
            #ffffff ${percent}%,
            #3a3a3a ${percent}%,
            #3a3a3a 100%
        )`;
    }

    getSelectedCharsets() {
        return {
            uppercase: this.charUppercase.checked,
            lowercase: this.charLowercase.checked,
            numbers: this.charNumbers.checked,
            symbols: this.charSymbols.checked,
        };
    }

    async generate() {
        const charsets = this.getSelectedCharsets();
        const length = parseInt(this.lengthSlider.value);

        // Validation
        if (!charsets.uppercase && !charsets.lowercase && !charsets.numbers && !charsets.symbols) {
            this.showToast('Select at least one character set', 'error');
            return;
        }

        const payload = {
            length,
            ...charsets,
            exclude_ambiguous: this.excludeAmbiguous.checked,
        };

        try {
            const response = await fetch('/api/generate-password', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.getCsrfToken(),
                },
                body: JSON.stringify(payload),
            });

            const data = await response.json();

            if (!response.ok || !data.success) {
                this.showToast(data.error || 'Failed to generate password', 'error');
                return;
            }

            this.currentPassword = data.data.password;
            this.currentMetadata = data.data;

            this.updateUI();
            this.showToast('Password generated!', 'success');
        } catch (error) {
            console.error('Generation error:', error);
            this.showToast('Error generating password', 'error');
        }
    }

    updateUI() {
        if (!this.currentMetadata) return;

        const meta = this.currentMetadata;

        // Update password display
        this.generatedPassword.textContent = this.currentPassword;
        this.savePassword.value = this.currentPassword;

        // Enable save button
        this.saveVaultBtn.disabled = false;

        if (this.generateBtnIcon) this.generateBtnIcon.textContent = 'autorenew';
        if (this.generateBtnText) this.generateBtnText.textContent = 'REGENERATE';
        this._applyPasswordScale(this.currentPassword);

        // Update strength badge
        this.strengthBadge.textContent = meta.strength.toUpperCase();
        this.strengthBadge.className = `strength-badge strength-${meta.strength.toLowerCase()}`;

        // Update strength bar
        const strengthPercent = this.getStrengthPercent(meta.strength);
        this.strengthFill.style.width = strengthPercent + '%';
        this.strengthFill.className = `strength-fill strength-${meta.strength.toLowerCase()}`;

        // Update entropy values
        this.entropyValue.textContent = meta.entropy;
        this.combinationsValue.textContent = meta.combinations;

        // Update analysis
        this.analysisLength.textContent = meta.length;
        this.analysisPools.textContent = this.formatPools(meta.selected_sets);
        this.analysisCombinations.textContent = meta.combinations;
        this.analysisCrackTime.textContent = meta.crack_time;
    }

    getStrengthPercent(strength) {
        const strengths = {
            'Weak': 33,
            'Medium': 66,
            'Strong': 100,
        };
        return strengths[strength] || 0;
    }

    formatPools(sets) {
        if (!sets || sets.length === 0) return '—';
        return sets.map(s => {
            const names = {
                'uppercase': 'Upper',
                'lowercase': 'Lower',
                'numbers': 'Nums',
                'symbols': 'Syms',
            };
            return names[s] || s;
        }).join(' + ');
    }

    async copyToClipboard() {
        if (!this.currentPassword) {
            this.showToast('Generate a password first', 'info');
            return;
        }

        try {
            await navigator.clipboard.writeText(this.currentPassword);
            this.showToast('Copied to clipboard!', 'success');

            // Visual feedback
            const originalHtml = this.copyBtn.innerHTML;
            this.copyBtn.innerHTML = '<span class="material-symbols-outlined">check</span>';
            setTimeout(() => {
                this.copyBtn.innerHTML = originalHtml;
            }, 2000);
        } catch (error) {
            console.error('Copy error:', error);
            this.showToast('Failed to copy', 'error');
        }
    }

    async saveToVault() {
        if (!this.currentPassword) {
            this.showToast('Generate a password first', 'error');
            return;
        }

        const service = this.saveService.value.trim();
        const url = this.saveUrl.value.trim();
        const email = this.saveEmail.value.trim();

        if (!service && !url && !email) {
            this.showToast('Enter at least one field', 'info');
            return;
        }

        if (window.vaultModal) {
            await window.vaultModal.demand();
        }

        try {
            const { ciphertext, iv } = await window.vaultCrypto.encrypt(this.currentPassword);

            const response = await fetch('/vault/entries', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.getCsrfToken(),
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    website: url || service,
                    nickname: service,
                    email_hint: email,
                    encrypted_password: ciphertext,
                    iv,
                    icon: this.selectedIconInput?.value || 'lock',
                }),
            });

            const data = await response.json();

            if (!response.ok || !data.success) {
                this.showToast(data.message ?? 'Failed to save to vault', 'error');
                return;
            }

            this.showToast('Password saved to vault!', 'success');
            this.saveService.value = '';
            this.saveUrl.value = '';
            this.saveEmail.value = '';
            this.savePassword.value = this.currentPassword;

        } catch (err) {
            console.error('Vault save error:', err);
            this.showToast('Error saving to vault', 'error');
        }
    }

    _enforceMinOneCharset(checkboxes) {
        const checkedCount = checkboxes.filter(cb => cb.checked).length;
        checkboxes.forEach(cb => {
            const isLastChecked = checkedCount === 1 && cb.checked;
            cb.disabled = isLastChecked;
            cb.closest('.checkbox-card')?.classList.toggle('disabled', isLastChecked);
        });
    }

    _selectIcon(iconName) {
        if (this.selectedIconDisplay) this.selectedIconDisplay.textContent = iconName;
        if (this.selectedIconInput) this.selectedIconInput.value = iconName;
        document.querySelectorAll('.icon-option').forEach(btn => {
            btn.classList.toggle('selected', btn.dataset.icon === iconName);
        });
        window.ModalSystem?.close('icon-picker');
    }

    _applyPasswordScale(password) {
        if (!this.generatedPassword || !password) return;
        const len = password.length;
        this.generatedPassword.classList.remove('pw-scale-lg', 'pw-scale-md', 'pw-scale-sm');
        if (len <= 32) {
            this.generatedPassword.classList.add('pw-scale-lg');
        } else if (len <= 72) {
            this.generatedPassword.classList.add('pw-scale-md');
        } else {
            this.generatedPassword.classList.add('pw-scale-sm');
        }
    }

    savePreferences() {
        const prefs = {
            length: this.lengthSlider.value,
            ...this.getSelectedCharsets(),
            exclude_ambiguous: this.excludeAmbiguous.checked,
        };
        localStorage.setItem('passwordGeneratorPrefs', JSON.stringify(prefs));
    }

    loadPreferences() {
        const saved = localStorage.getItem('passwordGeneratorPrefs');
        if (!saved) {
            this.updateSliderBackground();
            return;
        }

        const prefs = JSON.parse(saved);

        if (prefs.length) this.lengthSlider.value = prefs.length;
        if (prefs.uppercase !== undefined) this.charUppercase.checked = prefs.uppercase;
        if (prefs.lowercase !== undefined) this.charLowercase.checked = prefs.lowercase;
        if (prefs.numbers !== undefined) this.charNumbers.checked = prefs.numbers;
        if (prefs.symbols !== undefined) this.charSymbols.checked = prefs.symbols;
        if (prefs.exclude_ambiguous !== undefined) this.excludeAmbiguous.checked = prefs.exclude_ambiguous;

        this.lengthValue.textContent = this.lengthSlider.value;
        this.updateSliderBackground();
    }

    showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.textContent = message;

        document.body.appendChild(toast);

        // Trigger animation
        setTimeout(() => toast.classList.add('show'), 10);

        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    getCsrfToken() {
        const token = document.querySelector('meta[name="csrf-token"]');
        return token ? token.getAttribute('content') : '';
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    new PasswordGenerator();
});
