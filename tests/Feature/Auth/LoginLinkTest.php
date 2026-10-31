<?php

use App\Enums\LoginIntent;
use App\Mail\LoginLinkMail;
use App\Models\LoginLink;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Mail::fake();
});

function sentLink(): LoginLink
{
    $mail = Mail::queued(LoginLinkMail::class)->last();
    expect($mail)->not->toBeNull();

    return $mail->link;
}

test('signing up by email sends an activation link that creates the account in the chosen country', function () {
    $this->post(route('login-link.signup'), ['email' => ' Priya@Example.com ', 'country' => 'IN'])
        ->assertRedirect(route('login-link.sent'));

    $this->get(route('login-link.sent'))
        ->assertInertia(fn (Assert $page) => $page->component('auth/CheckInbox')
            ->where('email', 'priya@example.com')
            ->where('intent', 'signup'));

    $link = sentLink();
    Mail::assertQueued(LoginLinkMail::class, fn (LoginLinkMail $mail) => $mail->hasTo('priya@example.com'));
    expect(User::count())->toBe(0);

    $this->get($link->url())
        ->assertInertia(fn (Assert $page) => $page->component('auth/ConfirmLoginLink')->where('usable', true)->where('intent', 'signup'));

    $this->post($link->url())->assertRedirect(route('dashboard'));

    $user = User::sole();
    $this->assertAuthenticatedAs($user);
    expect($user->email)->toBe('priya@example.com')
        ->and($user->country?->code)->toBe('IN')
        ->and($user->email_verified_at)->not->toBeNull()
        ->and($user->password)->toBeNull();
});

test('logging in by email sends a login link for an existing account', function () {
    $user = User::factory()->create(['email' => 'sam@example.com']);

    $this->post(route('login-link.login'), ['email' => 'sam@example.com'])->assertRedirect(route('login-link.sent'));
    $this->post(sentLink()->url())->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($user);
    expect(User::count())->toBe(1);
});

test('logging in with an email that has no account switches to sign-up with it filled in', function () {
    $this->post(route('login-link.login'), ['email' => 'new@example.com'])
        ->assertRedirect(route('register', ['email' => 'new@example.com']))
        ->assertSessionHas('status');

    Mail::assertNothingQueued();

    $this->get(route('register', ['email' => 'new@example.com']))
        ->assertInertia(fn (Assert $page) => $page->component('auth/Register')
            ->where('email', 'new@example.com')
            ->has('countries', 5));
});

test('signing up with an email that already has an account sends a login link instead', function () {
    $user = User::factory()->create(['email' => 'sam@example.com']);

    $this->post(route('login-link.signup'), ['email' => 'sam@example.com', 'country' => 'US']);

    expect(sentLink()->intent->value)->toBe('login');
    $this->post(sentLink()->url());

    $this->assertAuthenticatedAs($user);
    expect(User::count())->toBe(1)
        ->and($user->fresh()?->country_id)->toBeNull();
});

test('a link stops working after 15 minutes', function () {
    $this->post(route('login-link.signup'), ['email' => 'priya@example.com', 'country' => 'IN']);
    $link = sentLink();

    $this->travel(16)->minutes();

    $this->get($link->url())->assertInertia(fn (Assert $page) => $page->where('usable', false));
    $this->post($link->url())->assertRedirect(route('login'))->assertSessionHas('status');

    $this->assertGuest();
    expect(User::count())->toBe(0);
});

test('a link works only once', function () {
    $user = User::factory()->create(['email' => 'sam@example.com']);
    $this->post(route('login-link.login'), ['email' => 'sam@example.com']);
    $link = sentLink();

    $this->post($link->url())->assertRedirect(route('dashboard'));
    auth('web')->logout();

    $this->get($link->url())->assertInertia(fn (Assert $page) => $page->where('usable', false));
    $this->post($link->url())->assertRedirect(route('login'));
    $this->assertGuest();
});

test('a link with a changed signature does nothing', function () {
    User::factory()->create(['email' => 'sam@example.com']);
    $this->post(route('login-link.login'), ['email' => 'sam@example.com']);
    $tampered = preg_replace('/signature=[0-9a-f]+/', 'signature='.str_repeat('0', 64), sentLink()->url());

    $this->get($tampered)->assertInertia(fn (Assert $page) => $page->where('usable', false));
    $this->post($tampered)->assertRedirect(route('login'));

    $this->assertGuest();
    expect(LoginLink::sole()->used_at)->toBeNull();
});

test('a malformed link is not found', function () {
    $this->get('/login/link/not-a-uuid')->assertNotFound();
});

test('sign-up needs a launch country', function () {
    $this->post(route('login-link.signup'), ['email' => 'priya@example.com', 'country' => 'FR'])
        ->assertSessionHasErrors('country');

    Mail::assertNothingQueued();
});

test('asking for too many links is slowed down', function () {
    User::factory()->create(['email' => 'sam@example.com']);

    foreach (range(1, 5) as $_) {
        $this->post(route('login-link.login'), ['email' => 'sam@example.com'])->assertRedirect();
    }

    $this->post(route('login-link.login'), ['email' => 'sam@example.com'])->assertTooManyRequests();
});

test('the email explains the link', function () {
    $link = LoginLink::issue('priya@example.com', LoginIntent::Signup);
    $mail = new LoginLinkMail($link);

    $mail->assertHasSubject('Activate your Rasoi account');
    $mail->assertSeeInHtml('Activate my account');
    $mail->assertSeeInText('The link works once, for 15 minutes.');
    $mail->assertSeeInHtml(e($link->url()), false);
});
