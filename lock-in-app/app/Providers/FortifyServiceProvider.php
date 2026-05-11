<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\DisableTwoFactorAuthentication;
use App\Actions\Fortify\ResetUserPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureActions();
        $this->configureViews();
        $this->configureNotifications();
        $this->configureRateLimiting();
    }

    /**
     * Configure Fortify actions.
     */
    private function configureActions(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::createUsersUsing(CreateNewUser::class);

        // Override Fortify's default disable-TOTP action so the server blocks
        // removing the last active MFA method.
        $this->app->bind(
            \Laravel\Fortify\Actions\DisableTwoFactorAuthentication::class,
            DisableTwoFactorAuthentication::class,
        );
    }

    /**
     * Configure Fortify views.
     */
    private function configureViews(): void
    {
        // Login and register are handled by custom Blade routes in web.php

        // 2FA challenge during login: Blade view
        Fortify::twoFactorChallengeView(fn () => view('auth.two-factor-challenge'));

        // Password reset: Blade views
        Fortify::requestPasswordResetLinkView(fn (Request $request) => view('auth.forgot-password', [
            'status' => $request->session()->get('status'),
        ]));

        Fortify::resetPasswordView(fn (Request $request) => view('auth.reset-password', [
            'email' => $request->query('email'),
            'token' => $request->route('token'),
        ]));

        Fortify::verifyEmailView(fn (Request $request) => view('auth.verify-email', [
            'status' => $request->session()->get('status'),
            'email' => $request->user()?->email,
        ]));

        Fortify::confirmPasswordView(fn () => Inertia::render('auth/confirm-password'));
    }

    private function configureNotifications(): void
    {
        VerifyEmail::toMailUsing(function (object $notifiable, string $url): MailMessage {
            return (new MailMessage)
                ->subject('Verify your LOCK IN email address')
                ->greeting('Welcome to LOCK IN, '.$notifiable->first_name.'!')
                ->line('Click the button below to verify your email address.')
                ->action('Verify Email Address', $url)
                ->line('This link expires in 60 minutes.')
                ->line('If you did not create a LOCK IN account, no action is required.');
        });
    }

    /**
     * Configure rate limiting.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });
    }
}
