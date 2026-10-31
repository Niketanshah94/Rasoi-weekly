<?php

use App\Models\User;
use Database\Seeders\CountrySeeder;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use Laravel\Socialite\Two\User as SocialiteUser;

beforeEach(function () {
    $this->seed(CountrySeeder::class);
    config(['services.google.client_id' => 'web-client', 'services.google.client_secret' => 'secret', 'services.google.redirect' => 'http://localhost/auth/google/callback']);
});

function googleReturns(string $id, string $email, string $name = 'Priya Iyer'): void
{
    $provider = Mockery::mock(GoogleProvider::class);
    $provider->shouldReceive('user')->andReturn((new SocialiteUser)->map(['id' => $id, 'email' => $email, 'name' => $name]));
    Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
}

test('Continue with Google sends the visitor to Google', function () {
    $this->get(route('google.redirect', ['country' => 'SG']))
        ->assertRedirectContains('accounts.google.com');

    expect(session('google.country'))->toBe('SG');
});

test('signing up with Google creates the account in the chosen country', function () {
    googleReturns('g-123', 'Priya@Example.com');

    $this->withSession(['google.country' => 'SG'])
        ->get(route('google.callback'))
        ->assertRedirect(route('dashboard'));

    $user = User::sole();
    $this->assertAuthenticatedAs($user);
    expect($user->only('email', 'name', 'google_id'))->toBe(['email' => 'priya@example.com', 'name' => 'Priya Iyer', 'google_id' => 'g-123'])
        ->and($user->country?->code)->toBe('SG')
        ->and($user->email_verified_at)->not->toBeNull();
});

test('logging in with Google links it to an account with the same email', function () {
    $user = User::factory()->create(['email' => 'priya@example.com', 'google_id' => null]);
    googleReturns('g-123', 'priya@example.com');

    $this->get(route('google.callback'))->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($user);
    expect($user->fresh()?->google_id)->toBe('g-123')
        ->and(User::count())->toBe(1);
});

test('logging in with an unknown Google account switches to sign-up', function () {
    googleReturns('g-999', 'new@example.com');

    $this->get(route('google.callback'))
        ->assertRedirect(route('register', ['email' => 'new@example.com']));

    $this->assertGuest();
    expect(User::count())->toBe(0);
});

test('Google sign-in is off until it is configured', function () {
    config(['services.google.client_id' => null]);

    $this->get(route('google.redirect'))->assertNotFound();
    $this->get(route('login'))->assertInertia(fn ($page) => $page->where('googleEnabled', false));
});
