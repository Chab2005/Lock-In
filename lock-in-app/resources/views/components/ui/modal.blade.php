@props(['id', 'title' => ''])

<div id="modal-{{ $id }}" class="app-modal" style="display:none;" role="dialog" aria-modal="true">
    <div class="app-modal-box">
        <div class="app-modal-header">
            @if($title)
                <h2 class="app-modal-title">{{ $title }}</h2>
            @else
                <div></div>
            @endif
            <button class="app-modal-close-btn" data-close-modal="{{ $id }}" type="button" aria-label="Close">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div class="app-modal-body">
            {{ $slot }}
        </div>
    </div>
</div>
