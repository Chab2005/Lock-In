# EmailJS 2FA Integration Guide

## Overview

Production-ready EmailJS 2FA service for sending OTP codes with:
- ✅ Input validation (email format, 6-digit OTP)
- ✅ Error handling with detailed logging
- ✅ Automatic retry logic (configurable)
- ✅ Anti-spam cooldown protection
- ✅ TypeScript types included
- ✅ Proper template parameter mapping

---

## Current Setup

### Configuration Location
**File**: `resources/views/layouts/auth.blade.php` (lines 21-26)

```php
window.EmailJSConfig = {
    PUBLIC_KEY:        'YOUR_EMAILJS_PUBLIC_KEY',
    SERVICE_ID:        'service_h7ciewk',
    TEMPLATE_2FA_ID:   'template_2fa',
    TEMPLATE_SHARE_ID: 'template_sq11ggw',
};
emailjs.init({ publicKey: window.EmailJSConfig.PUBLIC_KEY });
```

### Current Implementation Location
**File**: `resources/views/auth/email-otp-challenge.blade.php` (lines 70-98)

Currently sends OTP using inline Blade script. **This is now replaced by the new service.**

---

## New Service

### Location
`resources/js/services/emailjs-2fa.ts`

### Key Features

#### 1. Main Function: `send2FAEmail()`
```typescript
const result = await send2FAEmail(userEmail, otp, {
    maxRetries: 2,      // Retry up to 2 times on failure
    retryDelay: 1000,   // Wait 1s between retries
    cooldownMs: 5000,   // Prevent sends within 5s for same email
});

if (result.success) {
    console.log(result.message); // "2FA code sent successfully to user@example.com"
} else {
    console.error(result.message);
}
```

**Return Type**:
```typescript
{
    success: boolean;
    message: string;           // Human-readable status
    attempt?: number;          // Which attempt succeeded (1-3)
    error?: Error;            // Error object if failed
}
```

#### 2. Simple Variant: `send2FAEmailOnce()`
```typescript
// No retries, immediate result
const { success, message } = await send2FAEmailOnce('user@example.com', 123456);
```

#### 3. OTP Generator: `generateOtp()`
```typescript
const otp = generateOtp();  // Returns: "123456" (random 6-digit)
```

#### 4. Utility Functions
```typescript
// Anti-spam utilities
clearAllCooldowns();                        // Clear all cooldowns
clearCooldownForEmail('user@example.com');  // Clear for specific email

// Validation
isValidEmail('user@example.com');           // true/false
isValidOtp('123456');                       // true/false
isEmailJSConfigured();                      // true/false
```

---

## Template Parameters

The service automatically maps OTP data to EmailJS template variables:

```javascript
{
    to_email:   'user@example.com',
    otp_code:   '123456',
    expiration: '2:45:30 PM'  // 5 minutes from now, formatted
}
```

### Email Template (EmailJS)
Your template should use:
- `{{to_email}}` — Recipient email
- `{{otp_code}}` — 6-digit code
- `{{expiration}}` — Expiration time formatted

---

## Usage Examples

### Basic Usage in Login Flow
```typescript
import { send2FAEmail } from '@/services/emailjs-2fa';

async function handleLoginWithEmail(email, password) {
    // Authenticate user...
    const otp = await fetchOtpFromBackend(); // e.g., "123456"
    
    const result = await send2FAEmail(email, otp);
    
    if (!result.success) {
        showError(result.message);
        return;
    }
    
    navigateToOtpVerification();
}
```

### In React Component
```typescript
import { send2FAEmail } from '@/services/emailjs-2fa';
import { useState } from 'react';

export default function TwoFactorChallenge() {
    const [sending, setSending] = useState(false);
    const [status, setStatus] = useState('');
    
    const handleResendCode = async () => {
        setSending(true);
        setStatus('Requesting new code...');
        
        const result = await send2FAEmail(userEmail, newOtp, {
            maxRetries: 2,
            retryDelay: 1000,
            cooldownMs: 30_000,  // 30-second cooldown for resends
        });
        
        setStatus(result.message);
        setSending(false);
    };
    
    return (
        <>
            <p>{status}</p>
            <button onClick={handleResendCode} disabled={sending}>
                {sending ? 'Sending...' : 'Resend Code'}
            </button>
        </>
    );
}
```

### With Error Recovery
```typescript
import { send2FAEmail, clearCooldownForEmail } from '@/services/emailjs-2fa';

async function sendOtpWithRecovery(email, otp) {
    const result = await send2FAEmail(email, otp);
    
    if (!result.success) {
        // Log for monitoring
        logEvent('otp_send_failed', {
            email: email,
            error: result.error?.message,
            attempts: result.attempt,
        });
        
        // If cooldown was the issue, clear it for manual retry
        if (result.message.includes('wait')) {
            clearCooldownForEmail(email);
        }
        
        throw new Error(result.message);
    }
    
    return result;
}
```

---

## Validation Details

### Email Validation
- Uses regex: `/^[^\s@]+@[^\s@]+\.[^\s@]+$/`
- Checks for `user@domain.ext` format
- Case-insensitive

### OTP Validation
- Must be exactly 6 digits: `/^\d{6}$/`
- Accepts string or number input
- Pads with zeros (e.g., `123` → `000123`)

### Config Validation
- Checks for `window.emailjs` global
- Checks for `window.EmailJSConfig.SERVICE_ID`
- Checks for `window.EmailJSConfig.TEMPLATE_2FA_ID`
- Checks for `window.EmailJSConfig.PUBLIC_KEY`

---

## Error Handling

### Validation Errors (Immediate)
```
"Invalid email format: "not-an-email""
"Invalid OTP: must be exactly 6 digits (received: "1234")"
```

### Configuration Errors (Immediate)
```
"EmailJS not properly configured. Check console for details."
"[EmailJS 2FA] SERVICE_ID not configured in window.EmailJSConfig"
```

### Send Errors (With Retries)
```
"2FA code sent successfully to user@example.com"  // Success
"Failed to send 2FA code after 3 attempts: Network timeout"  // Failure
```

### Cooldown Errors
```
"Please wait 5s before sending another code to user@example.com"
```

---

## Logging

All events are logged to console with `[EmailJS 2FA]` prefix:

```javascript
[EmailJS 2FA] Sending OTP to: user@example.com
[EmailJS 2FA] Attempt 1 failed, retrying in 1000ms...
[EmailJS 2FA] Success: 2FA code sent successfully to user@example.com
[EmailJS 2FA] Final attempt failed: Network error
```

---

## Replacing Current Implementation

The current inline script in `email-otp-challenge.blade.php` (lines 70-98) can be replaced:

### Old Code (Current)
```blade
<script>
async function fetchAndSend() {
    const res = await fetch('/2fa/email/otp');
    const data = await res.json();
    
    await emailjs.send(
        window.EmailJSConfig.SERVICE_ID,
        window.EmailJSConfig.TEMPLATE_2FA_ID,
        {
            to_email: data.email,
            otp_code: data.otp,
        },
    );
}
</script>
```

### New Code (Refactored)
```typescript
import { send2FAEmail } from '@/services/emailjs-2fa';

async function fetchAndSend() {
    const res = await fetch('/2fa/email/otp');
    const data = await res.json();
    
    const result = await send2FAEmail(data.email, data.otp, {
        maxRetries: 2,
        cooldownMs: 30_000,  // 30s for resend button
    });
    
    if (!result.success) {
        console.error(result.message);
        return;
    }
    
    console.log(result.message);
}
```

**Benefits**:
- Centralized error handling
- Built-in validation
- Automatic retries
- Anti-spam protection
- Easy to test
- Reusable across the app

---

## Configuration Checklist

- [ ] EmailJS SDK loaded in `auth.blade.php`
- [ ] `PUBLIC_KEY` set in `window.EmailJSConfig`
- [ ] `SERVICE_ID` set (should be `service_h7ciewk`)
- [ ] `TEMPLATE_2FA_ID` set (template exists with `to_email`, `otp_code` variables)
- [ ] Backend generates valid 6-digit OTP
- [ ] Backend expiration set to ~5 minutes
- [ ] Frontend imports and calls `send2FAEmail()`

---

## Backend Integration

The backend already provides:
- `GET /2fa/email/otp` — Returns OTP and email (consumes once per session)
- `POST /2fa/email/resend` — Regenerates OTP with cooldown check
- OTP generation: 6 random digits, SHA256 hashed, 5-minute expiry
- Spam prevention: 60-second minimum between resends

See: `app/Http/Controllers/EmailTwoFactorController.php`

---

## Testing

### Unit Test Example
```typescript
import { send2FAEmail, generateOtp, clearAllCooldowns } from '@/services/emailjs-2fa';

describe('send2FAEmail', () => {
    beforeEach(() => clearAllCooldowns());
    
    it('validates email format', async () => {
        const result = await send2FAEmail('invalid-email', '123456');
        expect(result.success).toBe(false);
        expect(result.message).toContain('Invalid email format');
    });
    
    it('validates OTP format', async () => {
        const result = await send2FAEmail('user@example.com', '12345');  // 5 digits
        expect(result.success).toBe(false);
        expect(result.message).toContain('must be exactly 6 digits');
    });
    
    it('enforces cooldown', async () => {
        clearAllCooldowns();
        
        // Mock emailjs.send to succeed
        window.emailjs.send = () => Promise.resolve({});
        
        const result1 = await send2FAEmail('user@example.com', '123456', { cooldownMs: 5000 });
        expect(result1.success).toBe(true);
        
        const result2 = await send2FAEmail('user@example.com', '123456');
        expect(result2.success).toBe(false);
        expect(result2.message).toContain('wait');
    });
});
```

---

## Monitoring & Analytics

Track these events in your analytics:

```typescript
// Success
logEvent('2fa_email_sent', {
    email: userEmail,
    attempts: result.attempt,
    timestamp: new Date().toISOString(),
});

// Failure
logEvent('2fa_email_failed', {
    email: userEmail,
    error: result.error?.message,
    attempts: result.attempt,
    timestamp: new Date().toISOString(),
});
```

---

## Support

**File Location**: `resources/js/services/emailjs-2fa.ts`

**Dependencies**:
- EmailJS SDK (v4+)
- TypeScript support (optional but recommended)

**Requires**:
- `window.EmailJSConfig` with `SERVICE_ID`, `TEMPLATE_2FA_ID`, `PUBLIC_KEY`
- Backend OTP endpoint at `GET /2fa/email/otp`
- Backend resend endpoint at `POST /2fa/email/resend`

---

**Status**: ✅ Production Ready
