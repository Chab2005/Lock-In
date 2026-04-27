<main class="auth-card">

    <div class="badge">INITIALIZATION</div>

    <form class="auth-form" method="POST" action="{{ route('register') }}">
        @csrf

        <!-- NAME -->
        <div class="form-group">
            <label for="name">NAME</label>
            <input type="text" name="name" id="name" placeholder="Your name" required>
        </div>

        <!-- EMAIL -->
        <div class="form-group">
            <label for="email">EMAIL</label>
            <input type="email" name="email" id="email" placeholder="your@email.com" required>
        </div>

        <!-- PASSWORD -->
        <div class="form-group">
            <label for="password">PASSWORD</label>
            <input type="password" name="password" id="password" placeholder="••••••••••••" required>
        </div>

        <!-- CONFIRM PASSWORD -->
        <div class="form-group">
            <label for="password_confirmation">CONFIRM PASSWORD</label>
            <input type="password" name="password_confirmation" id="password_confirmation" placeholder="••••••••••••" required>
        </div>

        <!-- SUBMIT -->
        <button type="submit" class="btn-primary">
            CREATE ACCOUNT
            <span class="material-symbols-outlined">arrow_forward</span>
        </button>
    </form>
</main>
