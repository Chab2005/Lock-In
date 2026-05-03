# Laravel Blade Layout System

## Overview
This setup demonstrates a reusable Blade layout system following Laravel best practices. Each page extends a main layout that defines the global structure (header, footer, navigation), and injects page-specific content via `@yield()` and `@section()`.

---

## File Structure

```
resources/views/
├── layouts/
│   └── app.blade.php           # Main layout template (boilerplate)
├── components/
│   ├── header.blade.php        # Navigation header (included in layout)
│   └── footer.blade.php        # Footer (included in layout)
└── pages/
    ├── home.blade.php          # Home page (extends layout)
    ├── about.blade.php         # About page (extends layout)
    └── services.blade.php      # Services page (extends layout)
```

---

## How It Works

### 1. Main Layout: `layouts/app.blade.php`
- Defines the global HTML structure (DOCTYPE, meta tags, body)
- Includes shared components: `@include('components.header')` and `@include('components.footer')`
- Defines yield sections where child views inject content:
  - `@yield('title')` - Page title
  - `@yield('content')` - Page-specific content
  - `@yield('extra-styles')` - Optional per-page styles

### 2. Shared Components
- **header.blade.php**: Navigation bar with links
- **footer.blade.php**: Copyright and metadata

### 3. Page Views
Each page uses `@extends('layouts.app')` to inherit the layout, then defines sections:

```blade
@extends('layouts.app')

@section('title', 'Home - Lock In App')

@section('content')
    <h1>Welcome</h1>
    <!-- Page-specific content -->
@endsection
```

---

## Blade Features Used

| Feature | Purpose |
|---------|---------|
| `@extends('layouts.app')` | Child view inherits layout structure |
| `@section('name')` | Define a section in child view |
| `@yield('name')` | Placeholder in layout for section content |
| `@include('components.header')` | Include reusable components |
| `{{ }}` | Echo variables (e.g., `{{ date('Y') }}`) |
| `@endsection` | Close a section block |

---

## Usage in Routes

```php
// routes/web.php
Route::get('/', fn() => view('pages.home'));
Route::get('/about', fn() => view('pages.about'));
Route::get('/services', fn() => view('pages.services'));
```

---

## Content Flow Diagram

```
Request to /about
    ↓
Route returns view('pages.about')
    ↓
pages/about.blade.php extends layouts/app
    ↓
layouts/app renders HTML structure:
  - @include('components.header') → header.blade.php
  - @yield('content') ← FILLED WITH pages/about content
  - @include('components.footer') → footer.blade.php
    ↓
Browser receives complete HTML
```

---

## Key Laravel Best Practices Applied

✅ **Separation of Concerns**: Layout, components, and pages are separate files  
✅ **DRY (Don't Repeat Yourself)**: Common HTML (header, footer) defined once  
✅ **Reusability**: Components can be used across multiple layouts  
✅ **Clean Naming**: Clear naming convention (layouts/, components/, pages/)  
✅ **Scalability**: Easy to add new pages by extending the layout  
✅ **Maintainability**: Changes to header/footer propagate to all pages automatically  

---

## Extending This System

### Add a New Page
1. Create `resources/views/pages/contact.blade.php`
2. Use `@extends('layouts.app')`
3. Define `@section('title')` and `@section('content')`
4. Add route: `Route::get('/contact', fn() => view('pages.contact'))`

### Create a New Layout
For a different page structure (e.g., no header), create `resources/views/layouts/minimal.blade.php` and extend it instead of `layouts.app`.

### Add Page-Specific Styles
Use `@section('extra-styles')` to define custom CSS per page (see services.blade.php example).

---

## Testing the Layout

1. Start your Laravel dev server: `php artisan serve`
2. Visit: `http://localhost:8000/`
3. Navigate between Home, About, and Services
4. Verify that header and footer remain constant across all pages
5. Verify that content changes per page

---

**Built with Laravel Blade** ✨
