@extends('layouts.app')

@push('styles')
    @vite([
        'resources/css/dashboard.css',
        'resources/css/header_logged.css',
        'resources/css/footer_logged.css'
    ])
@endpush

@section('title','Lock In - Dashboard')

@section('header')
    <x-header_logged/>
@endsection

@section('content')
    @php
        $entries = $entries ?? collect();
        $receivedShares = $receivedShares ?? collect();
    @endphp

    @if (request()->query('verified') === '1')
        <div style="background: #16a34a1a; border: 1px solid #16a34a55; color: #22c55e;
                    font-size: 13px; padding: 10px 16px; border-radius: 6px; margin-bottom: 16px;">
            Your email has been verified. Welcome to LOCK IN!
        </div>
    @endif

    <x-dashboard.hero/>
    <x-dashboard.sectionHeader/>

    {{-- My vault entries --}}
    <section class="grid" id="vaultGrid">
        @forelse($entries as $entry)
            <x-dashboard.cardPassword :entry="$entry"/>
        @empty
            <p class="vault-empty-msg">
                No saved passwords yet.
                <a href="{{ route('generator') }}">Generate one →</a>
            </p>
        @endforelse
    </section>

    {{-- Shared with me --}}
    @if ($receivedShares->isNotEmpty())
        <div class="section-header" style="margin-top: 60px;">
            <h2 class="section-header">SHARED WITH ME</h2>
            <div class="divider"></div>
        </div>

        <section class="grid" id="sharedGrid" style="margin-top: 32px;">
            @foreach ($receivedShares as $share)
                <x-dashboard.sharedCard :share="$share"/>
            @endforeach
        </section>
    @endif

    {{-- Card detail modal --}}
    <x-ui.modal id="card-detail" title="ENTRY DETAILS">
        <div class="detail-header">
            <div class="detail-icon-box">
                <span class="material-symbols-outlined" id="detailIcon">lock</span>
            </div>
            <div>
                <h3 class="detail-title" id="detailTitle">—</h3>
                <p class="detail-website" id="detailWebsite"></p>
            </div>
        </div>

        <div class="detail-field">
            <label>Email</label>
            <p id="detailEmail">—</p>
        </div>

        <div class="detail-field">
            <label>Password</label>
            <div class="detail-pw-row">
                <span class="detail-pw-value" id="detailPasswordDots">••••••••••••</span>
                <div class="detail-pw-actions">
                    <span class="material-symbols-outlined" id="detailRevealBtn" title="Reveal">visibility</span>
                    <span class="material-symbols-outlined" id="detailCopyBtn" title="Copy">content_copy</span>
                </div>
            </div>
        </div>

        <div class="detail-field" id="detailNotesField" style="display: none;">
            <label>Notes</label>
            <p id="detailNotes" style="white-space: pre-wrap; word-break: break-word;">—</p>
        </div>

        <div class="detail-divider"></div>

        <div style="display: flex; gap: 12px; flex-wrap: wrap;">
            <button class="btn-secondary" id="detailEditBtn" type="button"
                    style="flex: 1; display: flex; align-items: center; justify-content: center; gap: 8px;">
                <span class="material-symbols-outlined">edit</span> EDIT
            </button>
            <button class="btn-secondary" id="detailShareBtn" type="button"
                    style="flex: 1; display: flex; align-items: center; justify-content: center; gap: 8px;">
                <span class="material-symbols-outlined">share</span> SHARE
            </button>
            <button class="btn-danger" id="detailDeleteBtn" type="button"
                    style="display: flex; align-items: center; justify-content: center; gap: 8px;">
                <span class="material-symbols-outlined">delete</span> DELETE
            </button>
        </div>
    </x-ui.modal>

    {{-- Share entry modal --}}
    <x-ui.modal id="share-entry" title="SHARE ENTRY">
        <div id="shareStep1">
            <p style="color: var(--color-text-secondary); font-size: 13px;
                      margin-bottom: 20px; line-height: 1.6;">
                Share a copy of this credential with another Lock In user.
                They will receive a share link — the password never leaves your device unencrypted.
            </p>

            <div style="margin-bottom: 16px;">
                <label for="shareRecipientEmail"
                       style="font-size: 11px; color: var(--color-text-secondary);
                              letter-spacing: 0.1em; text-transform: uppercase; display: block; margin-bottom: 8px;">
                    RECIPIENT EMAIL
                </label>
                <input type="email" id="shareRecipientEmail"
                       placeholder="colleague@example.com"
                       autocomplete="off"
                       style="width: 100%; background: transparent; border: none;
                              border-bottom: 1px solid var(--color-border); padding: 10px 0;
                              color: var(--color-text-primary); font-family: inherit; font-size: 14px;">
            </div>

            <p id="shareError"
               style="color: #ff3d3d; font-size: 13px; display: none; margin-bottom: 12px;"></p>

            <div class="modal-footer">
                <button type="button" class="btn-secondary"
                        onclick="window.ModalSystem?.close('share-entry')">CANCEL</button>
                <button type="button" class="btn-primary btn-expand" id="submitShareBtn">
                    CREATE SHARE LINK
                </button>
            </div>
        </div>

        <div id="shareStep2" style="display: none; text-align: center; padding: 8px 0;">
            <span class="material-symbols-outlined"
                  style="font-size: 48px; color: #4caf50; display: block; margin-bottom: 16px;">check_circle</span>
            <p style="font-size: 15px; color: var(--color-text-primary); margin-bottom: 8px; font-weight: 600;">
                Invitation sent!
            </p>
            <p style="font-size: 13px; color: var(--color-text-secondary); margin-bottom: 28px; line-height: 1.6;">
                A secure share link has been emailed to<br>
                <strong id="shareConfirmEmail" style="color: var(--color-text-primary);"></strong>
            </p>
            <button type="button" class="btn-primary" style="width: 100%;"
                    onclick="window.ModalSystem?.close('share-entry')">DONE</button>
        </div>
    </x-ui.modal>
    {{-- Edit entry modal --}}
    <x-ui.modal id="edit-entry" title="EDIT ENTRY">
        <div id="editFormWrap">
            <div style="margin-bottom: 20px;">
                <label for="editNickname"
                       style="font-size: 11px; color: var(--color-text-secondary);
                              letter-spacing: 0.1em; text-transform: uppercase;
                              display: block; margin-bottom: 8px;">
                    SERVICE NAME
                </label>
                <input type="text" id="editNickname" placeholder="GitHub, Gmail…"
                       autocomplete="off"
                       style="width: 100%; background: transparent; border: none;
                              border-bottom: 1px solid var(--color-border); padding: 10px 0;
                              color: var(--color-text-primary); font-family: inherit; font-size: 15px;">
            </div>

            <div style="margin-bottom: 20px;">
                <label for="editWebsite"
                       style="font-size: 11px; color: var(--color-text-secondary);
                              letter-spacing: 0.1em; text-transform: uppercase;
                              display: block; margin-bottom: 8px;">
                    WEBSITE / URL
                </label>
                <input type="url" id="editWebsite" placeholder="https://example.com"
                       autocomplete="off"
                       style="width: 100%; background: transparent; border: none;
                              border-bottom: 1px solid var(--color-border); padding: 10px 0;
                              color: var(--color-text-primary); font-family: inherit; font-size: 15px;">
            </div>

            <div style="margin-bottom: 20px;">
                <label for="editEmailHint"
                       style="font-size: 11px; color: var(--color-text-secondary);
                              letter-spacing: 0.1em; text-transform: uppercase;
                              display: block; margin-bottom: 8px;">
                    ACCOUNT (USERNAME / EMAIL)
                </label>
                <input type="text" id="editEmailHint" placeholder="user@example.com"
                       autocomplete="off"
                       style="width: 100%; background: transparent; border: none;
                              border-bottom: 1px solid var(--color-border); padding: 10px 0;
                              color: var(--color-text-primary); font-family: inherit; font-size: 15px;">
            </div>

            <div style="margin-bottom: 20px;">
                <label for="editNotes"
                       style="font-size: 11px; color: var(--color-text-secondary);
                              letter-spacing: 0.1em; text-transform: uppercase;
                              display: block; margin-bottom: 8px;">
                    NOTES
                </label>
                <textarea id="editNotes" placeholder="Recovery codes, hints…"
                          rows="3"
                          style="width: 100%; background: transparent; border: none;
                                 border-bottom: 1px solid var(--color-border); padding: 10px 0;
                                 color: var(--color-text-primary); font-family: inherit; font-size: 15px;
                                 resize: vertical; outline: none;"></textarea>
            </div>

            <div style="margin-bottom: 8px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                    <label style="font-size: 11px; color: var(--color-text-secondary);
                                  letter-spacing: 0.1em; text-transform: uppercase;">
                        PASSWORD
                    </label>
                    <button type="button" id="editTogglePasswordBtn"
                            onclick="VaultEdit.togglePasswordChange()"
                            style="background: none; border: none; color: var(--color-accent, #6c63ff);
                                   cursor: pointer; font-family: inherit; font-size: 11px;
                                   letter-spacing: 0.1em; text-transform: uppercase; padding: 0;">
                        CHANGE
                    </button>
                </div>
                <div id="editPasswordPlaceholder"
                     style="padding: 10px 0; border-bottom: 1px solid var(--color-border);
                            color: var(--color-text-secondary); letter-spacing: 0.25em; font-size: 15px;">
                    ••••••••••••
                </div>
                <div id="editPasswordInputWrap" style="display: none;">
                    <input type="password" id="editNewPassword" placeholder="New password"
                           autocomplete="new-password"
                           style="width: 100%; background: transparent; border: none;
                                  border-bottom: 1px solid var(--color-border); padding: 10px 0;
                                  color: var(--color-text-primary); font-family: inherit; font-size: 15px;">
                    <p id="editPasswordError"
                       style="color: #ff3d3d; font-size: 12px; margin-top: 4px; display: none;"></p>
                </div>
            </div>

            <p id="editGenericError"
               style="color: #ff3d3d; font-size: 13px; display: none; margin: 12px 0 0;"></p>

            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="VaultEdit.cancel()">CANCEL</button>
                <button type="button" class="btn-primary btn-expand" id="editSaveBtn" onclick="VaultEdit.save()">SAVE CHANGES</button>
            </div>
        </div>

        <div id="editSuccessWrap" style="display: none; text-align: center; padding: 8px 0;">
            <span class="material-symbols-outlined" style="font-size: 48px; color: #4cd137;">check_circle</span>
            <h3 style="font-family: 'Space Grotesk'; margin-top: 8px;">CHANGES SAVED</h3>
            <p style="color: var(--color-text-secondary); font-size: 13px; margin: 8px 0 24px;">
                Your vault entry has been updated.
            </p>
            <button type="button" class="btn-primary" style="width: 100%;"
                    onclick="VaultEdit.done()">DONE</button>
        </div>
    </x-ui.modal>

    {{-- Delete entry confirmation modal --}}
    <x-ui.modal id="delete-confirm" title="DELETE ENTRY">
        <p style="color: var(--color-text-secondary); font-size: 13px; line-height: 1.6; margin-bottom: 8px;">
            Are you sure you want to delete
            <strong id="deleteConfirmName" style="color: var(--color-text-primary);"></strong>?
        </p>
        <p style="color: var(--color-text-secondary); font-size: 13px; line-height: 1.6; margin-bottom: 24px;">
            This action cannot be undone.
        </p>
        <div class="modal-footer">
            <button type="button" class="btn-secondary btn-expand"
                    onclick="window.ModalSystem?.close('delete-confirm')">CANCEL</button>
            <button type="button" class="btn-danger btn-expand" id="deleteConfirmBtn">
                <span class="material-symbols-outlined">delete</span> DELETE
            </button>
        </div>
    </x-ui.modal>
@endsection

@section('footer')
    <x-footer/>
@endsection
