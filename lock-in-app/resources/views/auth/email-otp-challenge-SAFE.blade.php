<script>
document.addEventListener('DOMContentLoaded', () => {
    // ─────────────────────────────────────────────────────────────────────
    // Defensive Configuration & Early Returns
    // ─────────────────────────────────────────────────────────────────────

    // Get DOM elements with null safety
    const statusEl = document.getElementById('emailSendStatus');
    const resendBtn = document.getElementById('resendBtn');
    const resendMsg = document.getElementById('resendMsg');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    // Log config validation
    const isConfigValid = () => {
        if (typeof emailjs === 'undefined') {
            console.error('[2FA] EmailJS not loaded');
            return false;
        }
        if (!window.EmailJSConfig?.SERVICE_ID) {
            console.error('[2FA] EmailJSConfig.SERVICE_ID missing');
            return false;
        }
        if (!window.EmailJSConfig?.TEMPLATE_2FA_ID) {
            console.error('[2FA] EmailJSConfig.TEMPLATE_2FA_ID missing');
            return false;
        }
        return true;
    };

    // Safe DOM element setter
    const setStatus = (text, color) => {
        if (statusEl) {
            statusEl.textContent = text;
            if (color) statusEl.style.color = color;
        }
    };

    const setResendMsg = (text) => {
        if (resendMsg) {
            resendMsg.textContent = text;
            resendMsg.style.display = text ? '' : 'none';
        }
    };

    const setButtonEnabled = (enabled) => {
        if (resendBtn) {
            resendBtn.disabled = !enabled;
        }
    };

    // Early return if critical config missing
    if (!isConfigValid()) {
        setStatus('System configuration error. Please refresh the page.', '#ff3d3d');
        return;
    }

    // ─────────────────────────────────────────────────────────────────────
    // Main Email Send Logic
    // ─────────────────────────────────────────────────────────────────────

    async function fetchAndSend() {
        try {
            // Fetch OTP from backend
            const fetchRes = await fetch('/2fa/email/otp', {
                headers: { Accept: 'application/json' },
            });

            // Validate response
            if (!fetchRes.ok) {
                const errorData = await fetchRes.json().catch(() => ({}));
                setStatus(
                    errorData.error || 'No pending code — click Resend.',
                    '#ffc600'
                );
                console.warn('[2FA] Backend error:', errorData.error);
                return;
            }

            const data = await fetchRes.json().catch(() => null);

            // Validate JSON data
            if (!data || !data.email || !data.otp) {
                setStatus('Invalid response from server. Please refresh.', '#ff3d3d');
                console.error('[2FA] Invalid OTP data:', data);
                return;
            }

            // ─────────────────────────────────────────────────────────────
            // Safe EmailJS Send
            // ─────────────────────────────────────────────────────────────

            console.log('[2FA] Sending OTP to:', data.email);

            await emailjs.send(
                window.EmailJSConfig.SERVICE_ID,
                window.EmailJSConfig.TEMPLATE_2FA_ID,
                {
                    user_email: data.email,
                    otp_code: data.otp,
                    expiration: '5', // 5 minutes
                }
            );

            setStatus(
                'Code sent — check your inbox (including spam).',
                'var(--color-text-secondary)'
            );
            console.log('[2FA] Email sent successfully');
        } catch (err) {
            console.error('[2FA] Error:', err?.message || err);
            setStatus(
                'Could not send the code. Check your connection and click Resend.',
                '#ff3d3d'
            );
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    // Page Load Handler
    // ─────────────────────────────────────────────────────────────────────

    (async () => {
        console.log('[2FA] Page loaded, sending initial OTP...');
        await fetchAndSend();
    })();

    // ─────────────────────────────────────────────────────────────────────
    // Resend Button Handler (with null safety)
    // ─────────────────────────────────────────────────────────────────────

    if (resendBtn) {
        resendBtn.addEventListener('click', async () => {
            console.log('[2FA] Resend button clicked');

            // Prevent double-click
            if (resendBtn.disabled) {
                console.log('[2FA] Button already disabled, ignoring click');
                return;
            }

            // Disable button to prevent rapid clicks
            setButtonEnabled(false);
            setResendMsg('');
            setStatus('Requesting a new code…', 'var(--color-text-secondary)');

            try {
                // Request new OTP from backend
                const resendRes = await fetch('/2fa/email/resend', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                    },
                });

                // Parse response with error handling
                const resendData = await resendRes.json().catch(() => ({}));

                // Handle non-OK response
                if (!resendRes.ok) {
                    const errorMsg =
                        resendData.error ||
                        'Please wait before retrying.';
                    setResendMsg(errorMsg);
                    setStatus('', '');
                    console.warn('[2FA] Resend failed:', errorMsg);
                    setButtonEnabled(true); // Re-enable on error
                    return;
                }

                console.log('[2FA] New OTP generated, sending email...');

                // Send email with new OTP
                await fetchAndSend();
            } catch (err) {
                console.error('[2FA] Resend error:', err?.message || err);
                setResendMsg('Network error. Please try again.');
                setStatus('', '');
                setButtonEnabled(true); // Re-enable on error
                return;
            }

            // 30-second cooldown (disable button)
            console.log('[2FA] Starting 30s cooldown...');
            setTimeout(() => {
                console.log('[2FA] Cooldown expired, re-enabling button');
                setButtonEnabled(true);
            }, 30_000);
        });
    } else {
        console.warn('[2FA] Resend button not found in DOM');
    }

    console.log('[2FA] Script initialized successfully');
});
</script>
