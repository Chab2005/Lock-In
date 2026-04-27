<footer class="auth-footer">
    <p>
        @if (request()->routeIs('login'))
            Don't have an account? <a href="{{ route('register') }}">Sign up</a>
        @else
            Already have an account? <a href="{{ route('login') }}">Sign in</a>
        @endif
    </p>
</footer>
