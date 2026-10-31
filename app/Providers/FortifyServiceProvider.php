<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Http\Controllers\Auth\GoogleController;
use App\Models\Country;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Laravel\Fortify\Features;
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
        $this->configureRateLimiting();
    }

    /**
     * Configure Fortify actions.
     */
    private function configureActions(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::createUsersUsing(CreateNewUser::class);

        // Password sign-in is temporary (config rasoi.password_login); customers use Google or a link.
        Fortify::authenticateUsing(function (Request $request) {
            if (! config('rasoi.password_login')) {
                throw ValidationException::withMessages([Fortify::username() => 'Log in with Google or a login link by email.']);
            }

            $user = User::where('email', Str::lower((string) $request->input(Fortify::username())))->first();

            return $user?->password && Hash::check((string) $request->input('password'), $user->password) ? $user : null;
        });
    }

    /**
     * Configure Fortify views.
     */
    private function configureViews(): void
    {
        Fortify::loginView(fn (Request $request) => Inertia::render('auth/Login', [
            ...$this->signInOptions(),
            'canResetPassword' => Features::enabled(Features::resetPasswords()),
            'status' => $request->session()->get('status'),
            'email' => $request->query('email'),
        ]));

        Fortify::resetPasswordView(fn (Request $request) => Inertia::render('auth/ResetPassword', [
            'email' => $request->email,
            'token' => $request->route('token'),
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
        ]));

        Fortify::requestPasswordResetLinkView(fn (Request $request) => Inertia::render('auth/ForgotPassword', [
            'status' => $request->session()->get('status'),
        ]));

        Fortify::registerView(fn (Request $request) => Inertia::render('auth/Register', [
            ...$this->signInOptions(),
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
            'countries' => Country::orderBy('position')->get(['code', 'name']),
            'status' => $request->session()->get('status'),
            'email' => $request->query('email'),
        ]));
    }

    /**
     * Which ways to sign in the log-in and sign-up pages offer.
     *
     * @return array{googleEnabled: bool, passwordLogin: bool}
     */
    private function signInOptions(): array
    {
        return [
            'googleEnabled' => GoogleController::enabled(),
            'passwordLogin' => (bool) config('rasoi.password_login'),
        ];

    }

    /**
     * Configure rate limiting.
     */
    private function configureRateLimiting(): void
    {

        // Login links: a few per address, and a cap per IP so nobody floods inboxes.
        RateLimiter::for('login-link', fn (Request $request) => [
            Limit::perMinute(5)->by('email|'.Str::lower((string) $request->input('email')).'|'.$request->ip()),
            Limit::perMinute(20)->by('ip|'.$request->ip()),
        ]);

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

    }
}
