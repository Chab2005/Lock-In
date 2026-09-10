# Lock In

A zero-knowledge password manager built for a web development class (WEB IV). It has
two parts:

| Directory | What it is |
| --- | --- |
| [`lock-in-app/`](lock-in-app) | The Laravel 13 web application (server + vault UI) |
| [`lock-in-extension/`](lock-in-extension) | A Firefox/Chromium browser extension for autofill and quick capture |

The course requirements the project was built to satisfy:

- **Zero-knowledge** — the server never sees a plaintext password or the vault key.
- **A real domain name** with **HTTPS**, so all client/server communication is encrypted in transit.
- **Encrypted communication** end to end: TLS on the wire *plus* client-side
  AES-256-GCM so the payloads are already ciphertext before they leave the browser.

Agentic / AI-assisted programming was used heavily throughout (it was encouraged,
not required) — see [Agentic development](#agentic-development) below.

---

## How the zero-knowledge model works

Nothing that can decrypt a vault entry ever reaches the server.

### Key derivation

1. On first vault use the server generates a random per-user **salt** (32 bytes) and
   stores KDF parameters (`PBKDF2`, `310 000` iterations, `SHA-256`, 256-bit key).
   These are the only key-related values the server holds.
2. The client derives the **vault key** in the browser with the WebCrypto API:
   `PBKDF2(password, salt, 310 000, SHA-256) → AES-256-GCM key`.
   The key is created **non-extractable** — it never touches disk and never leaves
   `crypto.subtle`.
3. Each secret is encrypted client-side with AES-256-GCM using a fresh random 12-byte
   IV. The server only ever receives and stores `{ ciphertext, iv }`.

### The verifier

To confirm a derived key is correct *without* the server knowing the password, a
known constant (`LOCKIN_VAULT_V1`) is encrypted with the vault key and stored as
`vault_verifier`. On unlock the client fetches that blob and tries to decrypt it —
success proves the key is right, failure produces a clear "wrong vault password"
message. The plaintext constant is never sent; only its ciphertext is stored.

### Web app vs. extension

- The **web app** uses a dedicated *vault password* (independent of the login
  password), entered to unlock the vault for a session.
- The **extension** derives the vault key from the *login password* to avoid a
  second prompt. It uses the verifier to detect the case where the two passwords
  differ and tells the user to align them in the web app's settings.

Both implementations share the exact same crypto (`resources/js/vault.js` ⇄
`lock-in-extension/shared/crypto.js`) so ciphertext is interchangeable.

### Sharing (also zero-knowledge)

Sharing an entry with another user:

1. Client generates a random 256-bit **share key**, encrypts the entry with it.
2. Server stores only the encrypted payload, IV, and a **SHA-256 hash** of the
   share key — never the key itself.
3. The share key travels to the recipient in the URL **fragment** (`#key=…`) of the
   email link. Fragments are never sent to the server, so the recipient's browser
   decrypts locally.
4. Every share action (`created`, `viewed`, `claimed`, `revoked`) is written to an
   audit log with IP and user agent. Owners can revoke a share at any time.

---

## Security features

| Area | Implementation |
| --- | --- |
| Vault encryption | AES-256-GCM, client-side, per-entry random IV |
| Key derivation | PBKDF2-SHA-256, 310 000 iterations, non-extractable key |
| Account passwords | Hashed with bcrypt (`BCRYPT_ROUNDS=12`) via Laravel's `hashed` cast |
| 2FA — authenticator app | TOTP via Laravel Fortify (QR + recovery codes) |
| 2FA — email OTP | 6-digit code, SHA-256 hashed at rest, 5-minute expiry; on by default for new accounts |
| Passkeys / WebAuthn | Registration, login and credential management via `laragear/webauthn` |
| Session management | List active sessions, revoke one or "log out everywhere" |
| Extension auth | Bearer tokens (SHA-256 hashed in DB), no cookies, separate `/ext` API surface |
| Rate limiting | Per-route throttles (login, 2FA, share, vault, WebAuthn) |
| Transport | HTTPS + real domain required; secure-cookie support via `SESSION_SECURE_COOKIE` |
| Password generator | Cryptographic randomness (`random_int()`), entropy / crack-time reporting |
| Share links | Random 256-bit key in URL fragment, server stores only its hash, full audit log |

---

## Web application (`lock-in-app/`)

### Stack

- **Laravel 13**, PHP 8.3+ (CI runs 8.3 / 8.4 / 8.5)
- **Laravel Fortify** — authentication backend (2FA, password update, etc.)
- **`laragear/webauthn`** — passkey support
- Frontend: **Blade templates + vanilla JS** for the main app (login, register,
  dashboard, generator, settings). A **React / Inertia** layer is scaffolded (the
  Laravel React starter kit) and currently only backs the settings routes.
- **Vite** for asset bundling, custom CSS with CSS-variable light/dark theming
- SQLite by default (any Laravel-supported DB works)

### Layout

```
lock-in-app/
├── app/
│   ├── Http/Controllers/
│   │   ├── AuthController.php            # login / register / logout
│   │   ├── VaultController.php           # encrypted entry CRUD + salt/verifier
│   │   ├── ShareController.php           # zero-knowledge sharing + audit log
│   │   ├── ExtensionAuthController.php   # bearer-token auth for the extension
│   │   ├── EmailTwoFactorController.php  # email OTP challenge
│   │   ├── SessionController.php         # active session management
│   │   ├── PasswordGeneratorController.php
│   │   └── WebAuthn/                     # passkey register / login / credentials
│   ├── Models/                           # User, VaultEntry, SharedEntry,
│   │                                     # ShareAuditLog, ExtensionToken
│   ├── Services/PasswordGeneratorService.php
│   └── Mail/PasswordShared.php
├── resources/
│   ├── js/vault.js                       # client-side VaultCrypto (mirror of extension)
│   ├── js/generator.js
│   ├── views/                            # Blade pages + components
│   └── css/                              # per-page stylesheets
├── routes/web.php                        # all routes incl. /vault, /ext, /share, /webauthn
└── database/migrations/
```

### API surface (high level)

| Prefix | Auth | Purpose |
| --- | --- | --- |
| `/login`, `/register`, `/logout` | session | account auth |
| `/2fa/email/*` | pre-auth session | email OTP challenge |
| `/webauthn/*` | mixed | passkey registration & login |
| `/vault/*` | session | salt, verifier, encrypted entry CRUD |
| `/share/*` | session | create / claim / revoke shares |
| `/ext/*` | bearer token | extension login + vault CRUD (no CSRF, no session) |
| `/api/generate-password` | session | password generator |
| `/sessions/*` | session | active session management |

### Local setup

```bash
cd lock-in-app

composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite     # if using the default SQLite driver
php artisan migrate

npm install
npm run build      # or: npm run dev

# one-command dev environment (server + queue + logs + vite)
composer run dev
```

Then visit the app at `http://localhost:8000` (or whatever `php artisan serve` prints).

Email (password-share links, OTP) defaults to `MAIL_MAILER=log` — messages are
written to `storage/logs/`. Set real SMTP credentials in `.env` to send them
(examples for Mailtrap, Gmail, Resend/Mailgun are in `.env.example`).

### Production notes (domain + HTTPS)

- Point a real domain at the app and terminate TLS (the class required an actual
  domain name, not just `localhost`).
- Set `APP_URL=https://yourdomain.com` and `APP_ENV=production`, `APP_DEBUG=false`.
- Set `SESSION_SECURE_COOKIE=true` so session cookies are only sent over HTTPS.
- **WebAuthn**: set `WEBAUTHN_ID=yourdomain.com` (exact host, no port/path) and
  `WEBAUTHN_ORIGINS` for any extra origins. In development these fall back to
  `localhost` automatically.
- Update the extension's backend URL to the production domain (see below).

### Tests & CI

```bash
php artisan test            # or: ./vendor/bin/phpunit
composer lint               # Pint (PHP CS)
npm run lint:check          # ESLint
npm run types:check         # tsc --noEmit
```

GitHub Actions runs the PHPUnit suite on PHP 8.3/8.4/8.5 and a lint job
(Pint + Prettier + ESLint) on pushes and PRs to `main` / `develop`.
Feature tests cover auth, dashboard, vault CRUD and sharing.

---

## Browser extension (`lock-in-extension/`)

A WebExtension (Manifest V2, targets Firefox 109+ and Chromium) that talks to the
web app's `/ext` API.

### What it does

- **Login** with email + password → receives a bearer token, derives the vault key
  in the background page, verifies it against the server verifier.
- **Autofill** — a content script classifies username/password fields (regex
  heuristics, label association, SPA-aware via MutationObserver) and fills the
  best-matching credential on focus/click, or silently on page load when exactly
  one credential matches.
- **Capture** — detects submitted credentials and offers to save them to the vault.
- **Copy password** / **search** entries from the popup.
- All decryption happens in the background page; the vault key (a non-extractable
  `CryptoKey`) is held in memory only and cleared on logout or browser close.

### Layout

```
lock-in-extension/
├── manifest.json
├── popup/            # popup UI (login / vault / save-prompt / settings views)
├── background/       # persistent background page — key management + message router
├── content/          # autofill.js — field detection and fill engine
├── shared/
│   ├── crypto.js     # AES-256-GCM + PBKDF2 — byte-for-byte mirror of vault.js
│   ├── api.js        # /ext API client (bearer token in storage.local)
│   └── constants.js  # verifier plaintext, storage keys, message names
└── icons/
```

### Install (development)

**Firefox**

1. `about:debugging` → **This Firefox** → **Load Temporary Add-on…**
2. Select `lock-in-extension/manifest.json`.

**Chromium** (Chrome/Edge/Brave)

1. `chrome://extensions` → enable **Developer mode**
2. **Load unpacked** → select the `lock-in-extension/` folder.
   (MV2 support is required; on newer Chrome use a Firefox/Edge build if MV2 is disabled.)

### Configure the backend URL

Default is `http://localhost`. Open the popup → **settings** (gear) and set it to
your app's origin (e.g. `https://yourdomain.com`). The `manifest.json`
`permissions` list must include a matching host pattern for production use.

---

## Agentic development

AI-assisted / agentic coding was pushed hard on this project (encouraged by the
course, not mandatory). Traces of that workflow live in the repo:

- **`lock-in-app/AGENTS.md`** — Laravel Boost guidelines and project conventions
  fed to coding agents.
- **`lock-in-app/CLAUDE.md`** — guidance for Claude Code (commands, architecture,
  conventions).
- **`lock-in-app/.junie/`** — JetBrains Junie skills (`laravel-best-practices`,
  `fortify-development`, `inertia-react-development`, `wayfinder-development`,
  `tailwindcss-development`) and an MCP config.
- **`laravel/boost`** is installed as a dev dependency — an MCP server exposing
  project-aware tools (schema inspection, DB queries, doc search) to agents.

The codebase intentionally keeps behaviour covered by feature tests rather than
throwaway scripts, and the two crypto implementations are kept in lockstep with
explicit "mirror this file" comments so an agent editing one is told to update the
other.

---

## License

Coursework project. The Laravel application retains the MIT license of the
Laravel framework skeleton it was generated from.
