<header class="topbar">
    <div class="topbar-inner">

        <div class="logo">
            LOCK IN
            <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e3e3e3"><path d="M239.35-35.04q-47.63 0-80.4-32.78-32.78-32.77-32.78-80.4v-401.06q0-47.96 32.78-80.57 32.77-32.61 80.4-32.61h13.13v-73.06q0-96 66.38-163t161.14-67q94.76 0 161.14 67 66.38 67 66.38 163v73.06h13.13q47.63 0 80.4 32.61 32.78 32.61 32.78 80.57v401.06q0 47.63-32.78 80.4-32.77 32.78-80.4 32.78h-481.3Zm0-113.18h481.3v-401.06h-481.3v401.06Zm305.72-133.86q26.78-27.07 26.78-65.07 0-38-26.94-64.9-26.95-26.91-65.07-26.91-38.12 0-64.91 27.03-26.78 27.03-26.78 64.98 0 38.05 26.94 64.99 26.95 26.94 65.07 26.94 38.12 0 64.91-27.06ZM365.65-662.46h228.7v-73.06q0-48.21-33.35-82.52-33.34-34.31-81.12-34.31-47.77 0-81 34.31t-33.23 82.52v73.06Zm-126.3 514.24v-401.06 401.06Z"/></svg>
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

            <form method="POST" action="{{ route('logout') }}" style="display:inline;">
                @csrf
                <button type="submit" class="icon-btn">
                    <span class="material-symbols-outlined">logout</span>
                </button>
            </form>
        </div>

    </div>
</header>

