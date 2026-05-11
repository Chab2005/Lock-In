@props(['share'])
<a href="{{ route('share.claim', $share->id) }}"
   class="card"
   style="text-decoration: none; cursor: pointer; display: flex; flex-direction: column; justify-content: space-between;">
    <div class="card-top">
        <div class="card-title">
            <div class="icon">
                <span class="material-symbols-outlined">share</span>
            </div>
            <h3>{{ Str::upper($share->label ?? 'SHARED ENTRY') }}</h3>
        </div>
        <span class="badge">SHARED</span>
    </div>
    <div class="card-bottom">
        <p class="email">From {{ $share->owner->name }}</p>
        <div class="password-row">
            <span class="dots">••••••••••••</span>
        </div>
    </div>
</a>
