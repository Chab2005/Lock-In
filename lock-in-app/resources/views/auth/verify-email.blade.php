@extends('layouts.auth')

@push('styles')
    @vite(['resources/css/root.css', 'resources/css/login.css'])
@endpush

@section('title', 'LOCK IN - Verify Your Email')

@section('header')
    <x-auth.header/>
@endsection

@section('content')
<main class="auth-card">
    <div class="badge">VERIFY YOUR EMAIL</div>

    @if ($status === 'verification-link-sent')
        <p style="color: #22c55e; font-size: 13px; margin: 16px 0;">
            A new verification link has been sent.
        </p>
    @endif

    <p style="margin: 16px 0 6px; color: var(--color-text-secondary); font-size: 14px; line-height: 1.6;">
        A verification link was sent to
    </p>

    @if ($email ?? null)
        <p style="font-family: 'Space Grotesk'; font-size: 14px; color: var(--color-text-primary);
                  margin-bottom: 16px; letter-spacing: 0.05em;">
            {{ $email }}
        </p>
    @endif

    <p style="color: var(--color-text-secondary); font-size: 13px; margin-bottom: 24px; line-height: 1.5;">
        Click the link in that email to activate your account.
        If you don't see it, check your spam folder.
    </p>

    <form method="POST" action="{{ route('verification.send') }}" id="resendForm">
        @csrf
        <button type="submit" id="resendBtn" class="btn-primary" style="width: 100%;">
            RESEND VERIFICATION EMAIL
            <span class="material-symbols-outlined">send</span>
        </button>
    </form>

    <p id="resendCooldown"
       style="font-size: 12px; color: var(--color-text-secondary); text-align: center;
              margin-top: 8px; display: none;">
        You can resend in <span id="countdown"></span>s
    </p>

    <form method="POST" action="{{ route('logout') }}" style="margin-top: 12px;">
        @csrf
        <button type="submit"
                style="width: 100%; background: none; border: none; color: var(--color-text-secondary);
                       font-size: 13px; cursor: pointer; font-family: inherit; text-decoration: underline;">
            Log out
        </button>
    </form>
</main>
@endsection

@section('footer')
    <x-auth.footer/>
@endsection

@push('scripts')
<script>
document.getElementById('resendForm')?.addEventListener('submit', function () {
    const btn      = document.getElementById('resendBtn');
    const cooldown = document.getElementById('resendCooldown');
    const counter  = document.getElementById('countdown');
    if (!btn) return;

    btn.disabled = true;
    btn.style.opacity = '0.5';

    let seconds = 60;
    counter.textContent = seconds;
    cooldown.style.display = '';

    const timer = setInterval(() => {
        seconds -= 1;
        counter.textContent = seconds;
        if (seconds <= 0) {
            clearInterval(timer);
            btn.disabled = false;
            btn.style.opacity = '';
            cooldown.style.display = 'none';
        }
    }, 1000);
});
</script>
@endpush
