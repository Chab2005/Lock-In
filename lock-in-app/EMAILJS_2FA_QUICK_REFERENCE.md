# EmailJS 2FA — Quick Reference

## Import & Usage

```typescript
import { send2FAEmail, generateOtp } from '@/services/emailjs-2fa';

// Generate OTP (or use backend-provided OTP)
const otp = generateOtp();  // "123456"

// Send 2FA email
const result = await send2FAEmail('user@example.com', otp);

if (result.success) {
    console.log('Email sent!');
} else {
    console.error('Failed:', result.message);
}
```

---

## API Reference

### `send2FAEmail(email, otp, options?)`
Main function. Sends OTP with retries & cooldown.

**Parameters:**
- `email` (string): Recipient email
- `otp` (string|number): 6-digit code
- `options?`:
  - `maxRetries?: number` (default: 2)
  - `retryDelay?: number` (default: 1000ms)
  - `cooldownMs?: number` (default: 5000ms)

**Returns:**
```typescript
{
    success: boolean,
    message: string,
    attempt?: number,
    error?: Error
}
```

---

### `send2FAEmailOnce(email, otp)`
Simplified: no retries or cooldown. Good for testing.

**Returns:** Same as `send2FAEmail()`

---

### `generateOtp()`
Returns random 6-digit OTP string.

```typescript
generateOtp()  // "042857"
```

---

### Utility Functions

```typescript
isValidEmail(email: string): boolean
isValidOtp(otp: string | number): boolean
isEmailJSConfigured(): boolean

clearAllCooldowns(): void
clearCooldownForEmail(email: string): void
```

---

## Configuration Required

### 1. EmailJS Layout Setup
**File:** `resources/views/layouts/auth.blade.php` (lines 21-26)

```php
window.EmailJSConfig = {
    PUBLIC_KEY:        'YOUR_EMAILJS_PUBLIC_KEY',  // ← Add your key
    SERVICE_ID:        'service_h7ciewk',          // Already set
    TEMPLATE_2FA_ID:   'template_2fa',             // Already set
    TEMPLATE_SHARE_ID: 'template_sq11ggw',
};
emailjs.init({ publicKey: window.EmailJSConfig.PUBLIC_KEY });
```

### 2. EmailJS Template Variables
Your EmailJS template must include:
- `{{to_email}}` — Recipient
- `{{otp_code}}` — 6-digit code
- `{{expiration}}` — Auto-formatted time

---

## Examples

### React Component
```tsx
import { send2FAEmail } from '@/services/emailjs-2fa';
import { useState } from 'react';

export default function TwoFAForm() {
    const [loading, setLoading] = useState(false);
    const [status, setStatus] = useState('');
    
    const handleSendCode = async (email: string, otp: string) => {
        setLoading(true);
        
        const result = await send2FAEmail(email, otp, {
            maxRetries: 2,
            cooldownMs: 30_000,
        });
        
        setStatus(result.message);
        setLoading(false);
    };
    
    return (
        <div>
            <button onClick={() => handleSendCode('user@example.com', '123456')} disabled={loading}>
                {loading ? 'Sending...' : 'Send Code'}
            </button>
            <p>{status}</p>
        </div>
    );
}
```

### Vanilla JS
```javascript
import { send2FAEmail } from './services/emailjs-2fa.ts';

document.getElementById('sendBtn').addEventListener('click', async () => {
    const email = 'user@example.com';
    const otp = '123456';
    
    const result = await send2FAEmail(email, otp);
    
    alert(result.success ? '✓ Code sent' : '✗ ' + result.message);
});
```

### Backend Integration
```typescript
// Fetch OTP from backend, then send email
async function resendOtpEmail() {
    const response = await fetch('/2fa/email/otp');
    const { email, otp } = await response.json();
    
    const result = await send2FAEmail(email, otp, {
        cooldownMs: 30_000,
    });
    
    if (!result.success) {
        showNotification(result.message, 'error');
    }
}
```

---

## Error Messages

| Scenario | Message |
|----------|---------|
| Invalid email | `Invalid email format: "bad-email"` |
| Invalid OTP | `Invalid OTP: must be exactly 6 digits` |
| Not configured | `EmailJS not properly configured` |
| Cooldown active | `Please wait 5s before sending...` |
| Network error | `Failed to send 2FA code after 3 attempts: ...` |
| **Success** | `2FA code sent successfully to user@example.com` |

---

## Logging (Console)

All events logged with `[EmailJS 2FA]` prefix:

```
[EmailJS 2FA] Sending OTP to: user@example.com
[EmailJS 2FA] Attempt 1 failed, retrying in 1000ms...
[EmailJS 2FA] Success: 2FA code sent successfully...
```

---

## Common Configurations

### Fast Retry (Network Resilient)
```typescript
send2FAEmail(email, otp, {
    maxRetries: 3,
    retryDelay: 500,
})
```

### Slow Cooldown (Prevent Spam)
```typescript
send2FAEmail(email, otp, {
    cooldownMs: 60_000,  // 60 seconds
})
```

### No Frills (Testing)
```typescript
send2FAEmailOnce(email, otp)
```

---

## Verifying Setup

1. Check browser console for errors (search `[EmailJS`)
2. Verify `window.EmailJSConfig` in browser console:
   ```js
   console.log(window.EmailJSConfig);
   ```
3. Test send:
   ```js
   import { send2FAEmail } from '@/services/emailjs-2fa';
   send2FAEmail('your@email.com', '123456').then(r => console.log(r));
   ```

---

## File Location

📁 `resources/js/services/emailjs-2fa.ts`

---

**Status:** ✅ Production Ready
