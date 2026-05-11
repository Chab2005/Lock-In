@extends('layouts.auth')

@push('styles')
    @vite(['resources/css/root.css', 'resources/css/login.css'])
@endpush

@section('title', 'LOCK IN - Reset Password')

@section('header')
    <x-auth.header/>
@endsection

@section('content')
<main class="auth-card">
    <div class="badge">PASSWORD RESET</div>

    @if (session('status'))
        <p style="color: #4cd137; font-size: 14px; margin: 16px 0; line-height: 1.5;">
            {{ session('status') }}
        </p>
    @else
        <p style="margin: 16px 0 24px; color: var(--color-text-secondary); font-size: 14px; line-height: 1.5;">
            Enter your email and we'll send a reset link.
        </p>
    @endif

    @if ($errors->isNotEmpty())
        <p style="color: #ff3d3d; font-size: 13px; margin-bottom: 16px;">{{ $errors->first() }}</p>
    @endif

    <form class="auth-form" method="POST" action="/forgot-password">
        @csrf
        <div class="form-group">
            <label for="email">EMAIL</label>
            <input type="email" name="email" id="email"
                   value="{{ old('email') }}"
                   placeholder="your@email.com"
                   required autocomplete="email">
        </div>
        <button type="submit" class="btn-primary">
            SEND RESET LINK <span class="material-symbols-outlined">mail</span>
        </button>
    </form>

    <div class="divider"><span>OR</span></div>

    <div style="text-align: center;">
        <a href="/login" style="color: var(--color-text-secondary); font-size: 13px; text-decoration: underline;">
            ← Back to sign in
        </a>
    </div>
</main>
@endsection

@section('footer')
    <x-auth.footer/>
@endsection
