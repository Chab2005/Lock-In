@extends('layouts.auth')

@push('styles')
    @vite(['resources/css/root.css', 'resources/css/login.css'])
@endpush

@section('title', 'LOCK IN - Verify')

@section('header')
    <x-auth.header/>
@endsection

@section('content')
<main class="auth-card">
    <div class="badge">2FA REQUIRED</div>

    <p style="margin: 16px 0 24px; color: var(--color-text-secondary); font-size: 14px; line-height: 1.5;">
        Open your authenticator app and enter the 6-digit code.
    </p>

    @if ($errors->isNotEmpty())
        <p style="color: #ff3d3d; font-size: 13px; margin-bottom: 16px;">{{ $errors->first() }}</p>
    @endif

    <form id="totpForm" class="auth-form" method="POST" action="/two-factor-challenge">
        @csrf

        <div id="codeGroup" class="form-group">
            <label for="code">AUTHENTICATOR CODE</label>
            <input type="text" name="code" id="code"
                   inputmode="numeric" maxlength="6" pattern="[0-9]{6}"
                   autocomplete="one-time-code" placeholder="000000" autofocus>
        </div>

        <div id="recoveryGroup" class="form-group" style="display: none;">
            <label for="recovery_code">RECOVERY CODE</label>
            <input type="text" name="recovery_code" id="recovery_code"
                   autocomplete="off" placeholder="xxxx-xxxx-xxxx-xxxx">
        </div>

        <button type="submit" class="btn-primary">
            VERIFY <span class="material-symbols-outlined">arrow_forward</span>
        </button>
    </form>

    <button type="button" id="toggleRecoveryBtn"
            style="margin-top: 16px; background: none; border: none; color: var(--color-text-secondary);
                   font-size: 13px; cursor: pointer; font-family: inherit; text-decoration: underline;">
        Use a recovery code instead
    </button>
</main>
@endsection

@section('footer')
    <x-auth.footer/>
@endsection

<script>
document.getElementById('toggleRecoveryBtn').addEventListener('click', function () {
    const codeGroup = document.getElementById('codeGroup');
    const recoveryGroup = document.getElementById('recoveryGroup');
    const usingRecovery = recoveryGroup.style.display !== 'none';

    codeGroup.style.display = usingRecovery ? '' : 'none';
    recoveryGroup.style.display = usingRecovery ? 'none' : '';
    this.textContent = usingRecovery
        ? 'Use a recovery code instead'
        : 'Use authenticator code instead';

    if (usingRecovery) {
        document.getElementById('code').focus();
    } else {
        document.getElementById('recovery_code').focus();
    }
});
</script>
