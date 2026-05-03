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
    @php $entries = $entries ?? collect(); @endphp

        <x-dashboard.hero/>

        <x-dashboard.sectionHeader/>

        <section class="grid" id="vaultGrid">
            @forelse($entries as $entry)
                <x-dashboard.cardPassword :entry="$entry"/>
            @empty
                <p class="vault-empty-msg">No saved passwords yet. <a href="{{ route('generator') }}">Generate one →</a></p>
            @endforelse
        </section>

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
                    <span class="material-symbols-outlined" id="detailRevealBtn" title="Reveal password">visibility</span>
                    <span class="material-symbols-outlined" id="detailCopyBtn" title="Copy password">content_copy</span>
                </div>
            </div>
        </div>

        <div class="detail-divider"></div>

        <button class="btn-danger" id="detailDeleteBtn" type="button">
            <span class="material-symbols-outlined">delete</span>
            DELETE ENTRY
        </button>
    </x-ui.modal>
@endsection

@section('footer')
    <x-footer/>
@endsection

