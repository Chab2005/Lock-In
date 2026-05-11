@extends('layouts.auth')

@push('styles')
    @vite(['resources/css/root.css', 'resources/css/login.css'])
@endpush

@section('title', 'LOCK IN - Verify Email')

@section('header')
    <x-auth.header/>
@endsection

@section('content')
<main class="auth-card">
    <div class="badge">EMAIL VERIFICATION</div>

    <p style="margin: 16px 0 6px; color: var(--color-text-secondary); font-size: 14px; line-height: 1.5;">
        A 6-digit code is being sent to
    </p>
    <p style="font-family: 'Space Grotesk'; font-size: 14px; color: var(--color-text-primary); margin-bottom: 8px; letter-spacing: 0.05em;">
        {{ $maskedEmail }}
    </p>

    <p id="emailSendStatus"
       style="font-size: 12px; color: var(--color-text-secondary); margin-bottom: 20px;">
        Sending…
    </p>

    @if ($errors->isNotEmpty())
        <p style="color: #ff3d3d; font-size: 13px; margin-bottom: 16px;">{{ $errors->first() }}</p>
    @endif

    <form id="otpForm" class="auth-form" method="POST" action="{{ route('2fa.email.verify') }}">
        @csrf

        <div class="form-group">
            <label for="code">VERIFICATION CODE</label>
            <input type="text" name="code" id="code"
                   inputmode="numeric" maxlength="6" pattern="[0-9]{6}"
                   autocomplete="one-time-code" placeholder="000000" autofocus>
        </div>

        <button type="submit" class="btn-primary">
            VERIFY <span class="material-symbols-outlined">arrow_forward</span>
        </button>
    </form>

    <button type="button" id="resendBtn"
            style="margin-top: 16px; background: none; border: none; color: var(--color-text-secondary);
                   font-size: 13px; cursor: pointer; font-family: inherit; text-decoration: underline;">
        Resend code
    </button>

    <p id="resendMsg"
       style="font-size: 12px; color: #ff3d3d; display: none; margin-top: 8px;"></p>
</main>
@endsection

@section('footer')
    <x-auth.footer/>
@endsection

@push('scripts')
    {{--
        Load the EmailJS 2FA service as a Vite module.
        Execution order:
          1. This <script type="module"> is deferred — runs after HTML parsing.
          2. The inline <script> below runs synchronously, registers DOMContentLoaded.
          3. Module runs, sets window.emailjs2fa.
          4. DOMContentLoaded fires — listener calls window.emailjs2fa safely.
    --}}
    <script src="https://cdn.jsdelivr.net/npm/@emailjs/browser@4/dist/email.min.js"></script>
    @vite(['resources/js/services/emailjs-2fa.ts'])

    <script>
    document.addEventListener('DOMContentLoaded', () => {


        // ─── EmailJS Configuration ────────────────────────────────────────────────
        // Edit these three values to match your EmailJS account:
        //   PUBLIC_KEY     → Account → API Keys → Public Key
        //   TEMPLATE_2FA_ID  → the template that sends the OTP code (variables: to_email, otp_code)
        //   TEMPLATE_SHARE_ID → the template that sends a share link (variables: to_email, from_name, entry_label, share_url)
        window.EmailJSConfig = {
            PUBLIC_KEY:        'ba4kj6EA7k-sKfOWu',
            SERVICE_ID:        'service_h7ciewk',
            TEMPLATE_2FA_ID:   'template_2fa',
            TEMPLATE_SHARE_ID: 'template_sq11ggw',
        };
        emailjs.init({ publicKey: window.EmailJSConfig.PUBLIC_KEY });




        // ── DOM refs ──────────────────────────────────────────────────────
        const statusEl  = document.getElementById('emailSendStatus');
        const resendBtn = document.getElementById('resendBtn');
        const resendMsg = document.getElementById('resendMsg');
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

        // ── UI helpers ────────────────────────────────────────────────────
        const setStatus = (text, color = '') => {
            if (!statusEl) return;
            statusEl.textContent = text;
            if (color) statusEl.style.color = color;
        };
        const setResendMsg = (text) => {
            if (!resendMsg) return;
            resendMsg.textContent   = text;
            resendMsg.style.display = text ? '' : 'none';
        };
        const setButtonEnabled = (on) => {
            if (resendBtn) resendBtn.disabled = !on;
        };

        // ── Race-condition guard ──────────────────────────────────────────
        // Prevents a page-load send and a resend from running concurrently.
        let isSending = false;

        // ── Service accessor ─────────────────────────────────────────────
        // The TS module sets window.emailjs2fa when it runs (before DOMContentLoaded).
        const getService = () => {
            if (typeof window.emailjs2fa?.send2FAEmail !== 'function') {
                console.error('[2FA] emailjs2fa service not available on window');
                setStatus('Service not loaded. Please refresh the page.', '#ff3d3d');
                return null;
            }
            return window.emailjs2fa;
        };

        // ── Core: fetch OTP from session → delegate to TS service ─────────
        // isResend=true bypasses the client-side cooldown (backend already rate-limits).
        async function fetchAndSend(isResend = false) {
            if (isSending) return;
            isSending = true;

            try {
                const res = await fetch('/2fa/email/otp', {
                    headers: { Accept: 'application/json' },
                });

                let data;
                try   { data = await res.json(); }
                catch { data = {}; }

                if (!res.ok) {
                    setStatus(data.error || 'No pending code — click Resend.', '#ffc600');
                    return;
                }

                if (!data?.email || !data?.otp) {
                    setStatus('Incomplete server response. Click Resend.', '#ff3d3d');
                    return;
                }

                const service = getService();
                if (!service) return;

                // Delegate entirely to the TS service (retry, validation, cooldown).
                const result = await service.send2FAEmail(
                    data.email,
                    data.otp,
                    { cooldownMs: isResend ? 0 : 5_000 },
                );

                if (result.success) {
                    setStatus(
                        'Code sent — check your inbox (including spam).',
                        'var(--color-text-secondary)',
                    );
                } else {
                    setStatus(result.message || 'Could not send code. Click Resend.', '#ff3d3d');
                }
            } catch (err) {
                console.error('[2FA] fetchAndSend error:', err);
                setStatus('Network error. Check your connection and click Resend.', '#ff3d3d');
            } finally {
                isSending = false;
            }
        }

        // ── Initial send on page load ─────────────────────────────────────
        fetchAndSend();

        // ── Resend button ─────────────────────────────────────────────────
        if (resendBtn) {
            resendBtn.addEventListener('click', async () => {
                if (resendBtn.disabled || isSending) return;

                setButtonEnabled(false);
                setResendMsg('');
                setStatus('Requesting a new code…', 'var(--color-text-secondary)');

                try {
                    const res = await fetch('/2fa/email/resend', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            Accept: 'application/json',
                            'Content-Type': 'application/json',
                        },
                    });

                    let data;
                    try   { data = await res.json(); }
                    catch { data = {}; }

                    if (!res.ok) {
                        setResendMsg(data.error || 'Please wait before retrying.');
                        setStatus('', '');
                        setButtonEnabled(true);
                        return;
                    }

                    // Backend issued a new OTP — fetch it and send (bypass cooldown).
                    await fetchAndSend(true);
                } catch (err) {
                    console.error('[2FA] Resend error:', err);
                    setResendMsg('Network error. Please try again.');
                    setStatus('', '');
                    setButtonEnabled(true);
                    return;
                }

                // 30-second UI cooldown (independent of TS-service cooldown).
                setTimeout(() => setButtonEnabled(true), 30_000);
            });
        }
    });
    </script>
@endpush
