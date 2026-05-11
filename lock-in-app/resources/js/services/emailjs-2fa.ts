/**
 * EmailJS 2FA Email Service
 * Handles sending 2FA OTP codes via EmailJS with proper error handling, validation, and retry logic.
 *
 * Dependencies:
 * - EmailJS SDK (loaded globally)
 * - window.EmailJSConfig.SERVICE_ID and TEMPLATE_2FA_ID must be configured
 *
 * @see resources/views/layouts/auth.blade.php
 */

let isInitialized = false;

function initEmailJS(): boolean {
    if (isInitialized) return true;

    if (typeof emailjs === 'undefined') {
        console.error('[EmailJS 2FA] SDK not loaded');
        return false;
    }

    if (!window.EmailJSConfig?.PUBLIC_KEY) {
        console.error('[EmailJS 2FA] PUBLIC_KEY missing');
        return false;
    }

    try {
        emailjs.init({
            publicKey: window.EmailJSConfig.PUBLIC_KEY,
        });

        isInitialized = true;
        return true;
    } catch (err) {
        console.error('[EmailJS 2FA] Init failed:', err);
        return false;
    }
}


interface EmailJSSendParams {
    user_email: string;
    otp_code: string | number;
    expiration?: number;
    [key: string]: string | number | undefined;
}

interface Send2FAEmailOptions {
    maxRetries?: number;
    retryDelay?: number;
    cooldownMs?: number;
}

interface Send2FAEmailResult {
    success: boolean;
    message: string;
    attempt?: number;
    error?: Error;
}

/**
 * Validates email format
 */
function isValidEmail(email: string): boolean {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

/**
 * Validates OTP format (6 digits)
 */
function isValidOtp(otp: string | number): boolean {
    const otpStr = String(otp).trim();
    return /^\d{6}$/.test(otpStr);
}

/**
 * Checks if EmailJS is properly initialized
 */
function isEmailJSConfigured(): boolean {
    if (typeof emailjs === 'undefined') {
        console.error('[EmailJS 2FA] EmailJS SDK not loaded. Add to layout: <script src="https://cdn.jsdelivr.net/npm/@emailjs/browser@4/dist/email.min.js"></script>');
        return false;
    }

    if (!window.EmailJSConfig?.SERVICE_ID) {
        console.error('[EmailJS 2FA] SERVICE_ID not configured in window.EmailJSConfig');
        return false;
    }

    if (!window.EmailJSConfig?.TEMPLATE_2FA_ID) {
        console.error('[EmailJS 2FA] TEMPLATE_2FA_ID not configured in window.EmailJSConfig');
        return false;
    }

    if (!window.EmailJSConfig?.PUBLIC_KEY) {
        console.error('[EmailJS 2FA] PUBLIC_KEY not configured in window.EmailJSConfig');
        return false;
    }

    return true;
}

/**
 * Formats the expiration time (default: 5 minutes from now)
 */
function formatExpiration(minutesFromNow: number = 5): string {
    const now = new Date();
    const expiresAt = new Date(now.getTime() + minutesFromNow * 60_000);
    return expiresAt.toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: true,
    });
}

// Track cooldown to prevent rapid-fire sends
const sendCooldowns = new Map<string, number>();

/**
 * Checks if email is in cooldown (anti-spam protection)
 */
function isInCooldown(email: string, cooldownMs: number): boolean {
    const lastSendTime = sendCooldowns.get(email);
    if (!lastSendTime) return false;

    const elapsed = Date.now() - lastSendTime;
    return elapsed < cooldownMs;
}

/**
 * Sets cooldown for an email
 */
function setCooldown(email: string): void {
    sendCooldowns.set(email, Date.now());
}

/**
 * Sends 2FA OTP email via EmailJS
 *
 * @param userEmail - Recipient email address
 * @param otp - 6-digit OTP code
 * @param options - Configuration options (retries, delays, cooldown)
 * @returns Result object with success status and message
 *
 * @example
 * const result = await send2FAEmail('user@example.com', 123456);
 * if (result.success) {
 *   console.log(result.message);
 * } else {
 *   console.error(result.message);
 * }
 */
export async function send2FAEmail(
    userEmail: string,
    otp: string | number,
    options: Send2FAEmailOptions = {},
): Promise<Send2FAEmailResult> {
    const {
        maxRetries = 2,
        retryDelay = 1000,
        cooldownMs = 5000,
    } = options;

    // ─── Input Validation ────────────────────────────────────────────────
    if (!userEmail) {
        return {
            success: false,
            message: 'Invalid email: must be a non-empty string',
        };
    }

    if (!isValidEmail(userEmail.trim())) {
        return {
            success: false,
            message: `Invalid email format: "${userEmail}"`,
        };
    }

    if (!isValidOtp(otp)) {
        return {
            success: false,
            message: `Invalid OTP: must be exactly 6 digits (received: "${otp}")`,
        };
    }

    // ─── Anti-Spam Check ────────────────────────────────────────────────
    if (isInCooldown(userEmail, cooldownMs)) {
        const remaining = cooldownMs - (Date.now() - (sendCooldowns.get(userEmail) || 0));
        const secondsLeft = Math.ceil(remaining / 1000);
        return {
            success: false,
            message: `Please wait ${secondsLeft}s before sending another code to ${userEmail}`,
        };
    }

    // ─── EmailJS Config Check ───────────────────────────────────────────
    if (!isEmailJSConfigured() || !initEmailJS()) {
        return {
            success: false,
            message: 'EmailJS not properly configured. Check console for details.',
        };
    }

    // ─── Prepare Template Parameters ─────────────────────────────────────
    const templateParams: EmailJSSendParams = {
        user_email: userEmail.trim(),
        otp_code: String(otp).trim(),
        expiration: 5, // ✅ simple + matches template
    };

    // ─── Retry Logic ────────────────────────────────────────────────────
    for (let attempt = 1; attempt <= maxRetries + 1; attempt++) {
        try {
            await emailjs.send(
                'service_h7ciewk',
                'template_sq11ggw',
                {
                    user_email: userEmail.trim(),
                    otp_code: String(otp).trim(),
                    expiration: 5,
                }
            );

            setCooldown(userEmail);

            return {
                success: true,
                message: `2FA code sent successfully to ${userEmail}`,
                attempt,
            };
        } catch (error: any) {
            console.error('[EmailJS FULL ERROR]', error);

            if (error?.text) {
                console.error('[EmailJS ERROR TEXT]', error.text);
            }

            const err = error instanceof Error
                ? error
                : new Error(JSON.stringify(error));


        }
    }

    // Should never reach here, but just in case
    return {
        success: false,
        message: 'Unexpected error: retry loop failed',
    };
}

/**
 * Sends 2FA email without retry logic (for simple use cases)
 *
 * @param userEmail - Recipient email address
 * @param otp - 6-digit OTP code
 * @returns Result object with success status and message
 *
 * @example
 * const { success, message } = await send2FAEmailOnce('user@example.com', 123456);
 */
export async function send2FAEmailOnce(
    userEmail: string,
    otp: string | number,
): Promise<Send2FAEmailResult> {
    return send2FAEmail(userEmail, otp, { maxRetries: 0, cooldownMs: 0 });
}

/**
 * Generates a random 6-digit OTP
 *
 * @returns 6-digit OTP code as a string
 *
 * @example
 * const otp = generateOtp();
 * console.log(otp); // e.g., "123456"
 */
export function generateOtp(): string {
    return String(Math.floor(Math.random() * 1_000_000))
        .padStart(6, '0');
}

/**
 * Clears all email cooldowns (useful for testing or admin actions)
 *
 * @example
 * clearAllCooldowns();
 */
export function clearAllCooldowns(): void {
    sendCooldowns.clear();
}

/**
 * Clears cooldown for a specific email
 *
 * @example
 * clearCooldownForEmail('user@example.com');
 */
export function clearCooldownForEmail(email: string): void {
    sendCooldowns.delete(email);
}

// Expose to window so non-module Blade scripts can call send2FAEmail
// without importing. Runs once when this module is first loaded.
if (typeof window !== 'undefined') {
    window.emailjs2fa = { send2FAEmail };
}

// Global type augmentation for EmailJS
declare global {
    interface Window {
        emailjs?: typeof emailjs;
        emailjs2fa?: { send2FAEmail: typeof send2FAEmail };
        EmailJSConfig?: {
            PUBLIC_KEY: string;
            SERVICE_ID: string;
            TEMPLATE_2FA_ID: string;
            TEMPLATE_SHARE_ID?: string;
        };
    }
}

// Type augmentation for emailjs library
declare const emailjs: {
    init: (config: { publicKey: string }) => void;
    send: (
        serviceId: string,
        templateId: string,
        templateParams: Record<string, any>,
    ) => Promise<any>;
};

export default {
    send2FAEmail,
    send2FAEmailOnce,
    generateOtp,
    clearAllCooldowns,
    clearCooldownForEmail,
    isValidEmail,
    isValidOtp,
    isEmailJSConfigured,
};
