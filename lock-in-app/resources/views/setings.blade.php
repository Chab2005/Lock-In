@extends('layouts.app')

@push('styles')
    @vite([
        'resources/css/root.css',
        'resources/css/checkbox.css',
        'resources/css/settings.css',
        'resources/css/header_logged.css',
        'resources/css/footer_logged.css',
    ])
@endpush

@section('title','Lock In - Settings')

@section('header')
    <x-header_logged/>
@endsection

@section('content')
    <section class="settings-page ">
        <div class="settings-container ">
            <div class="settings-header">
                <h1 class="settings-title">SETTINGS</h1>
            </div>

            <div class="info-banner">
                <div class="info-icon">
                    <span class="material-symbols-outlined">
                        shield_lock
                    </span>
                </div>
                <div class="info-content">
                    <h3>ZERO-KNOWLEDGE ARCHITECTURE</h3>
                    <p>Your data is end-to-end encrypted locally before leaving your device. We have no way to decrypt, read, or access your vault. The security of your information relies entirely on your master key.</p>
                </div>
            </div>

            <div class="settings-grid">
                <div class="grid-left">
                    <x-settings.authentifications/>
                    <x-settings.passkey/>
                </div>

                <div class="grid-right">
                    <x-settings.colorMode/>
                    <x-settings.deleteAccount/>
                </div>

            </div>
        </div>
    </section>
@endsection

@section('footer')
    <x-footer/>
@endsection
