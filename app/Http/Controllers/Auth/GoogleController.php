<?php

namespace App\Http\Controllers\Auth;

use App\Auth\CustomerSignIn;
use App\Auth\GoogleIdentity;
use App\Http\Controllers\Controller;
use App\Models\Country;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use Laravel\Socialite\Two\InvalidStateException;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirect;

/**
 * Google sign-in on the web. The mobile apps use the native plugin and the API instead.
 */
class GoogleController extends Controller
{
    public static function enabled(): bool
    {
        return filled(config('services.google.client_id')) && filled(config('services.google.client_secret'));
    }

    /**
     * Sign-up passes the chosen country; log-in passes none.
     */
    public function redirect(Request $request): SymfonyRedirect
    {
        abort_unless(self::enabled(), 404);

        $data = $request->validate(['country' => ['nullable', Rule::exists(Country::class, 'code')]]);
        $request->session()->put('google.country', $data['country'] ?? null);

        /** @var GoogleProvider $google */
        $google = Socialite::driver('google');

        return $google->scopes(['openid', 'email', 'profile'])->redirect();
    }

    public function callback(Request $request, CustomerSignIn $signIn): RedirectResponse
    {
        abort_unless(self::enabled(), 404);

        try {
            $google = Socialite::driver('google')->user();
        } catch (InvalidStateException) {
            return to_route('login')->with('status', 'Google sign-in didn’t finish. Please try again.');
        }

        $code = $request->session()->pull('google.country');
        $country = $code ? Country::where('code', $code)->first() : null;
        $user = $signIn->fromGoogle(new GoogleIdentity((string) $google->getId(), (string) $google->getEmail(), $google->getName()), $country);

        if (! $user) {
            return to_route('register', ['email' => $google->getEmail()])
                ->with('status', 'There’s no account for that Google account yet. Choose your country to create one.');
        }

        Auth::guard('web')->login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }
}
