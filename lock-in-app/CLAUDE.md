# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

All commands run from `lock-in-app/`.

```bash
# Start full dev environment (Laravel + Vite)
composer run dev

# Backend only
php artisan serve

# Frontend only
npm run dev

# Build frontend assets
npm run build

# Run tests
php artisan test --compact
php artisan test --compact tests/Feature/Auth/AuthenticationTest.php
php artisan test --compact --filter=testName

# PHP formatting (run after any PHP change)
vendor/bin/pint --dirty --format agent

# TypeScript / lint checks
npm run types:check
npm run lint:check
```

## Architecture

### Tech Stack

- **Backend**: Laravel 13, PHP 8.3+, Fortify for auth
- **Frontend**: Blade templates with vanilla JS (main app), plus a React/Inertia layer (scaffolded but not yet used for main pages)
- **Styling**: Custom CSS via Vite, organized per-page/component; CSS variables for light/dark theming in `resources/css/root.css`
- **Asset bundling**: Vite with `laravel-vite-plugin`

### Two parallel frontend approaches

The repo has two frontend styles that coexist:

1. **Blade + vanilla JS** — the active approach for all current pages (`login`, `register`, `dashboard`, `generator`, `setings`). Views in `resources/views/`, styles in `resources/css/`, and vanilla JS in `resources/js/generator.js`.
2. **React/Inertia** — scaffolded but only used by the settings routes from `routes/settings.php`. React components live in `resources/js/pages/` and `resources/js/components/`. Wayfinder-generated typed route functions are in `resources/js/actions/`.

New features for the main app (dashboard, generator) should follow the Blade + vanilla JS pattern until there's a decision to migrate.

### Blade layout system

Pages extend `layouts/app` (authenticated) or `layouts/auth` (guest). The main layout uses `@yield` sections:

```blade
@extends('layouts.app')
@push('styles')
    @vite(['resources/css/mypage.css', 'resources/js/myscript.js'])
@endpush
@section('header') <x-header_logged/> @endsection
@section('content') ... @endsection
@section('footer') <x-footer/> @endsection
```

Blade components are in `resources/views/components/`, namespaced by feature (`generator/`, `auth/`, `dashboard/`, `settings/`).

### Password generator flow

`POST /api/generate-password` → `PasswordGeneratorController::generate()` → `PasswordGeneratorService::generate()` → JSON response.

The frontend (`resources/js/generator.js`) is a single `PasswordGenerator` class that calls the endpoint, updates the DOM, and persists user preferences to `localStorage`. The service returns entropy, strength, crack time, and combinations alongside the password.

### Authentication

Custom `AuthController` handles login/register/logout with standard `Auth::attempt`. Laravel Fortify is installed for 2FA and advanced auth features (settings pages). The `/ ` route redirects authenticated users to `/dashboard`.

### Theme system

Dark/light mode is managed via a CSS class on `<html>`. `resources/js/color-mode.js` runs before page render (loaded via Vite in every layout) to apply the saved theme from `localStorage` without flash. CSS variables for both themes are defined in `resources/css/root.css`.

## Conventions

- CSS is split per page/component and pushed via `@vite` in each view's `@push('styles')` block — do not import everything globally.
- Blade component names use camelCase filenames (`cardPassword.blade.php`) referenced as `<x-componentName/>`.
- PHP follows PSR-4 autoloading; run Pint after every PHP change.
- Use `random_int()` (not `rand()`) for any cryptographic/security-sensitive randomness.
