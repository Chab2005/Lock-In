@props(['entry'])
<div class="card vault-card"
     data-entry-id="{{ $entry->id }}"
     data-encrypted="{{ $entry->encrypted_password }}"
     data-iv="{{ $entry->iv }}"
     data-icon="{{ $entry->icon ?? 'lock' }}"
     data-nickname="{{ $entry->nickname ?? '' }}"
     data-website="{{ $entry->website ?? '' }}"
     data-email="{{ $entry->email_hint ?? '' }}"
     role="button"
     tabindex="0"
     title="Click to view details">
    <div class="card-top">
        <div class="card-title">
            <div class="icon">
                <span class="material-symbols-outlined">{{ $entry->icon ?? 'lock' }}</span>
            </div>
            <h3>{{ Str::upper($entry->nickname ?: ($entry->website ?: 'ENTRY')) }}</h3>
        </div>
        <button class="vault-delete-btn" data-id="{{ $entry->id }}" title="Delete entry" type="button">
            <span class="material-symbols-outlined">delete</span>
        </button>
    </div>
    <div class="card-bottom">
        <p class="email">{{ $entry->email_hint }}</p>
        <div class="password-row">
            <span class="vault-dots dots">••••••••••••</span>
            <span class="material-symbols-outlined copy vault-copy-btn" title="Copy password">content_copy</span>
        </div>
    </div>
</div>
