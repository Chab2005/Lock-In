<div class="settings-section" id="sessionsSection">
    <div class="section-header-flex">
        <h2 class="section-title">Active Sessions</h2>
        <button class="btn-action" onclick="Sessions.promptLogoutOthers()">
            REVOKE ALL OTHERS
        </button>
    </div>

    <div class="settings-card" id="sessionList">
        <div class="card-item" style="color: var(--color-text-secondary); font-size: 13px;">
            Loading…
        </div>
    </div>

    <p id="sessionError" style="color:#ff3d3d; font-size:13px; margin-top:8px; display:none;"></p>
</div>

{{-- Logout others confirmation modal --}}
<x-ui.modal id="logout-others" title="REVOKE ALL OTHER SESSIONS">
    <p style="color: var(--color-text-secondary); font-size: 13px; margin-bottom: 16px; line-height: 1.6;">
        This will immediately sign out all other devices. Enter your password to confirm.
    </p>
    <div class="form-group">
        <label for="logoutOthersPassword">PASSWORD</label>
        <input type="password" id="logoutOthersPassword" placeholder="••••••••••••"
               style="width:100%; background:transparent; border:none;
                      border-bottom:1px solid var(--color-border); padding:10px 0;
                      color:var(--color-text-primary); font-family:inherit; font-size:16px;">
    </div>
    <p id="logoutOthersError" style="color:#ff3d3d; font-size:13px; display:none; margin-top:8px;"></p>
    <div style="display:flex; gap:12px; margin-top:20px;">
        <button class="btn-secondary" onclick="window.ModalSystem?.close('logout-others')">CANCEL</button>
        <button class="btn-primary" style="flex:1;" onclick="Sessions.logoutOthers()">CONFIRM</button>
    </div>
</x-ui.modal>

@push('scripts')
<script>
const Sessions = {
    async load() {
        const list = document.getElementById('sessionList');
        if (!list) return;

        const res = await fetch('/sessions', { headers: { Accept: 'application/json' } });
        if (!res.ok) {
            list.innerHTML = '<div class="card-item" style="font-size:13px;color:var(--color-text-secondary)">Could not load sessions.</div>';
            return;
        }

        const sessions = await res.json();
        const now = Math.floor(Date.now() / 1000);

        list.innerHTML = sessions.map(s => {
            const ago    = this._ago(now - s.last_activity);
            const badge  = s.is_current
                ? '<span class="activeBadge" style="margin-right:8px;">CURRENT</span>'
                : '';
            const revoke = s.is_current ? '' : `
                <button class="btn-icon" onclick="Sessions.revoke('${this._esc(s.id)}')" title="Revoke session">
                    <span class="material-symbols-outlined">logout</span>
                </button>`;

            return `
                <div class="card-item" data-session-id="${this._esc(s.id)}">
                    <div class="item-info">
                        <div class="item-icon">
                            <span class="material-symbols-outlined">devices</span>
                        </div>
                        <div class="item-text">
                            <h4>${badge}${this._esc(s.device)}</h4>
                            <p>${this._esc(s.ip)} · Last active ${ago}</p>
                        </div>
                    </div>
                    ${revoke}
                </div>`;
        }).join('');
    },

    async revoke(id) {
        const res = await fetch(`/sessions/${encodeURIComponent(id)}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content ?? '',
                Accept: 'application/json',
            },
        });

        const errEl = document.getElementById('sessionError');
        if (res.ok) {
            document.querySelector(`[data-session-id="${CSS.escape(id)}"]`)?.remove();
        } else {
            const data = await res.json().catch(() => ({}));
            errEl.textContent = data.message ?? 'Could not revoke session.';
            errEl.style.display = '';
        }
    },

    promptLogoutOthers() {
        document.getElementById('logoutOthersPassword').value = '';
        document.getElementById('logoutOthersError').style.display = 'none';
        window.ModalSystem?.open('logout-others');
        setTimeout(() => document.getElementById('logoutOthersPassword').focus(), 100);
    },

    async logoutOthers() {
        const pw    = document.getElementById('logoutOthersPassword').value;
        const errEl = document.getElementById('logoutOthersError');

        const res = await fetch('/sessions/logout-others', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content ?? '',
                Accept: 'application/json',
            },
            body: JSON.stringify({ password: pw }),
        });

        if (res.ok) {
            window.ModalSystem?.close('logout-others');
            await this.load();
        } else {
            const data = await res.json().catch(() => ({}));
            errEl.textContent = data.message ?? 'Incorrect password.';
            errEl.style.display = '';
        }
    },

    _ago(seconds) {
        if (seconds < 60)   return 'just now';
        if (seconds < 3600) return `${Math.floor(seconds / 60)}m ago`;
        if (seconds < 86400)return `${Math.floor(seconds / 3600)}h ago`;
        return `${Math.floor(seconds / 86400)}d ago`;
    },

    _esc(s) {
        return String(s).replace(/[&<>"']/g, c =>
            ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c]);
    },
};

document.addEventListener('DOMContentLoaded', () => Sessions.load());
</script>
@endpush
