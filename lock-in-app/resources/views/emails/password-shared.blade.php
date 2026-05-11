<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password shared with you</title>
    <style>
        body { margin: 0; padding: 0; background: #0d0d0d; font-family: 'Arial', sans-serif; color: #e0e0e0; }
        .wrapper { max-width: 560px; margin: 40px auto; background: #141414; border: 1px solid #2a2a2a; padding: 48px 40px; }
        .logo { font-size: 11px; letter-spacing: 0.3em; text-transform: uppercase; color: #666; margin-bottom: 40px; }
        h1 { font-size: 24px; font-weight: 700; color: #f0f0f0; margin: 0 0 12px; letter-spacing: -0.02em; }
        p { font-size: 14px; line-height: 1.7; color: #999; margin: 0 0 20px; }
        .entry-box { background: #1a1a1a; border: 1px solid #2a2a2a; padding: 20px 24px; margin: 28px 0; }
        .entry-label { font-size: 11px; letter-spacing: 0.15em; text-transform: uppercase; color: #666; margin-bottom: 6px; }
        .entry-name { font-size: 18px; font-weight: 700; color: #f0f0f0; }
        .btn { display: inline-block; background: #f0f0f0; color: #0d0d0d; padding: 16px 32px;
               font-weight: 700; font-size: 12px; letter-spacing: 0.15em; text-transform: uppercase;
               text-decoration: none; margin: 8px 0 28px; }
        .warning { background: rgba(255,198,0,0.08); border-left: 3px solid #ffc600;
                   padding: 12px 16px; font-size: 12px; color: #ffc600; line-height: 1.5; margin: 20px 0; }
        .footer { font-size: 11px; color: #444; margin-top: 40px; padding-top: 24px; border-top: 1px solid #2a2a2a; line-height: 1.6; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="logo">{{ config('app.name') }}</div>

    <h1>{{ $share->owner->name }} shared a password with you</h1>

    <p>
        You've received a secure credential share on {{ config('app.name') }}.
        Click the link below to access it — the decryption key is embedded in the URL fragment
        and is never sent to our servers.
    </p>

    <div class="entry-box">
        <div class="entry-label">Entry</div>
        <div class="entry-name">{{ $share->label ?? 'Shared Entry' }}</div>
    </div>

    <a href="{{ $shareUrl }}" class="btn">VIEW SHARED ENTRY</a>

    <div class="warning">
        Anyone with this link can decrypt the shared password. Only open it on a trusted device.
    </div>

    <p>
        If you weren't expecting this, you can safely ignore this email.
        The entry will remain accessible until the sender revokes it.
    </p>

    <div class="footer">
        This email was sent by {{ config('app.name') }} because {{ $share->owner->name }}
        ({{ $share->owner->email }}) shared a credential with {{ $share->recipient_email }}.<br>
        Do not forward this email — the link contains a sensitive decryption key.
    </div>
</div>
</body>
</html>
