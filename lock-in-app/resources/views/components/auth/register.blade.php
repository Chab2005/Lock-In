<main class="auth-card">

    <div class="badge">INITIALIZATION</div>

    <form class="auth-form" method="POST" action="{{ route('register') }}">
        @csrf

        <!-- FIRST NAME -->
        <div class="form-group">
            <label for="first_name">FIRST NAME</label>
            <input type="text" name="first_name" id="first_name" placeholder="John" required
                   value="{{ old('first_name') }}">
        </div>

        <!-- LAST NAME -->
        <div class="form-group">
            <label for="last_name">LAST NAME</label>
            <input type="text" name="last_name" id="last_name" placeholder="Doe" required
                   value="{{ old('last_name') }}">
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
