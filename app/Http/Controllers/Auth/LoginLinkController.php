<?php

namespace App\Http\Controllers\Auth;

use App\Auth\CustomerSignIn;
use App\Enums\LoginIntent;
use App\Http\Controllers\Controller;
use App\Mail\LoginLinkMail;
use App\Models\Country;
use App\Models\LoginLink;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * One-time login links by email (spec §1): signed, valid for 15 minutes, single use.
 */
class LoginLinkController extends Controller
{
    public function __construct(private CustomerSignIn $signIn) {}

    /**
     * Log in: email a link, or switch to sign-up when there is no account (spec §1).
     */
    public function sendLogin(Request $request): RedirectResponse
    {
        $email = $this->signIn->normalise($request->validate(['email' => ['required', 'string', 'email', 'max:255']])['email']);

        if (! User::where('email', $email)->exists()) {
            return to_route('register', ['email' => $email])
                ->with('status', 'There’s no account for that email yet. Choose your country to create one.');
        }

        return $this->send($request, LoginLink::issue($email, LoginIntent::Login));
    }

    /**
     * Sign up: email an activation link. An existing account gets a login link instead.
     */
    public function sendSignup(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'country' => ['required', Rule::exists(Country::class, 'code')],
        ]);
        $email = $this->signIn->normalise($data['email']);

        $link = User::where('email', $email)->exists()
            ? LoginLink::issue($email, LoginIntent::Login)
            : LoginLink::issue($email, LoginIntent::Signup, Country::where('code', $data['country'])->firstOrFail());

        return $this->send($request, $link);
    }

    public function checkInbox(Request $request): Response|RedirectResponse
    {
        if (! $request->session()->has('login_link.email')) {
            return to_route('login');
        }

        return Inertia::render('auth/CheckInbox', [
            'email' => $request->session()->get('login_link.email'),
            'intent' => $request->session()->get('login_link.intent'),
            'country' => $request->session()->get('login_link.country'),
            'minutes' => LoginLink::VALID_MINUTES,
        ]);
    }

    /**
     * Opening the link shows a button rather than signing in straight away, so email
     * scanners that open links can't use it up.
     */
    public function show(Request $request, LoginLink $loginLink): Response
    {
        return Inertia::render('auth/ConfirmLoginLink', [
            'email' => $loginLink->email,
            'intent' => $loginLink->intent->value,
            'usable' => $request->hasValidSignature() && $loginLink->isUsable(),
            // The confirm button posts back to this same signed address.
            'action' => $request->fullUrl(),
        ]);
    }

    public function consume(Request $request, LoginLink $loginLink): RedirectResponse
    {
        if (! $request->hasValidSignature() || ! $loginLink->claim()) {
            return to_route('login')->with('status', 'That link has expired or was already used. Get a new one below.');
        }

        $user = $this->signIn->fromEmail($loginLink->email, $loginLink->country);
        if (! $user) {
            return to_route('register', ['email' => $loginLink->email])
                ->with('status', 'There’s no account for that email yet. Choose your country to create one.');
        }

        Auth::guard('web')->login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    private function send(Request $request, LoginLink $link): RedirectResponse
    {
        Mail::to($link->email)->send(new LoginLinkMail($link));

        $request->session()->put('login_link', [
            'email' => $link->email,
            'intent' => $link->intent->value,
            'country' => $link->country?->code,
        ]);

        return to_route('login-link.sent');
    }
}
