<header class="topbar">
    <div class="topbar-inner">

        <div class="logo">
            LOCK IN
        </div>

        <nav class="nav">
            <a class="{{ request()->routeIs('dashboard') ? 'active' : '' }}"  href="{{ route('dashboard') }}">DashBoard</a>
            <a class="{{ request()->routeIs('generator') ? 'active' : '' }}" href="{{ route('generator') }}">Generator</a>
            <a class="{{ request()->routeIs('setings') ? 'active' : '' }}" href="{{ route('setings') }}">Settings</a>
        </nav>

        <div class="top-actions">
            <a class="icon-btn" href="{{ route('setings') }}">
                <span class="material-symbols-outlined">account_circle</span>
            </a>
        </div>

    </div>
</header>

