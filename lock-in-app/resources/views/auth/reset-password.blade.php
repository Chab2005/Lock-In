@extends('layouts.auth')

@push('styles')
    @vite(['resources/css/root.css', 'resources/css/login.css'])
@endpush

@section('title', 'LOCK IN - New Password')

@section('header')
    <x-auth.header/>
@endsection

@section('content')
<main class="auth-card">
    <div class="badge">NEW PASSWORD</div>

    <div style="background: rgba(255,198,0,0.1); border-left: 3px solid #ffc600;
                padding: 12px 16px; margin: 16px 0; font-size: 13px;
                color: #ffc600; line-height: 1.5;">
        <span class="material-symbols-outlined"
              style="font-size: 16px; vertical-align: middle; margin-right: 6px;">warning</span>
        Your encrypted vault will be cleared after reset. Vault data cannot be recovered without the original password.
    </div>

    @if ($errors->isNotEmpty())
        <div style="color: #ff3d3d; font-size: 13px; margin-bottom: 16px;">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form class="auth-form" method="POST" action="/reset-password">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <input type="hidden" name="email" value="{{ $email }}">

        <div class="form-group">
            <label for="password">NEW PASSWORD</label>
            <input type="password" name="password" id="password"
                   placeholder="••••••••••••" required autocomplete="new-password">
        </div>

        <div class="form-group">
            <label for="password_confirmation">CONFIRM PASSWORD</label>
            <input type="password" name="password_confirmation" id="password_confirmation"
                   placeholder="••••••••••••" required autocomplete="new-password">
        </div>

        <button type="submit" class="btn-primary">
            RESET PASSWORD <span class="material-symbols-outlined">lock_reset</span>
        </button>
    </form>
</main>
@endsection

@section('footer')
    <x-auth.footer/>
@endsection
