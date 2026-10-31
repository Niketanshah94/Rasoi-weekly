<?php

use App\Auth\GoogleIdTokenVerifier;
use App\Models\User;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Http;

/**
 * A local RSA key stands in for Google's: tokens are signed with it and its public half is
 * served as Google's key set, so the real verification runs without the network.
 */
beforeEach(function () {
    config(['services.google.client_id' => 'web-client', 'services.google.android_client_id' => 'android-client']);

    $this->googleKey = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
    $details = openssl_pkey_get_details($this->googleKey);
    $b64 = fn (string $bytes) => rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');

    Http::fake([GoogleIdTokenVerifier::KEYS_URL => Http::response(['keys' => [[
        'kty' => 'RSA', 'alg' => 'RS256', 'use' => 'sig', 'kid' => 'test-key',
        'n' => $b64($details['rsa']['n']), 'e' => $b64($details['rsa']['e']),
    ]]])]);
});

/**
 * @param  array<string, mixed>  $claims
 */
function googleToken(array $claims = [], mixed $key = null): string
{
    $key ??= test()->googleKey;
    openssl_pkey_export($key, $pem);

    return JWT::encode([
        'iss' => 'https://accounts.google.com',
        'aud' => 'android-client',
        'sub' => 'g-123',
        'email' => 'priya@example.com',
        'email_verified' => true,
        'name' => 'Priya Iyer',
        'iat' => time(),
        'exp' => time() + 3600,
        ...$claims,
    ], $pem, 'RS256', 'test-key');
}

test('the app exchanges a Google token for an API token and a web session', function () {
    $response = $this->postJson(route('api.auth.google'), ['id_token' => googleToken(), 'country' => 'IN'])
        ->assertOk()
        ->assertJsonPath('user.email', 'priya@example.com')
        ->assertJsonPath('user.country', 'IN');

    $user = User::sole();
    $this->assertAuthenticatedAs($user, 'web');
    expect($user->google_id)->toBe('g-123');

    auth('web')->logout();
    $this->withToken($response->json('token'))->getJson('/api/user')->assertOk()->assertJsonPath('email', 'priya@example.com');
});

test('a new Google account without a country is asked for one', function () {
    $this->postJson(route('api.auth.google'), ['id_token' => googleToken()])
        ->assertUnprocessable()
        ->assertJson(['needs_country' => true, 'email' => 'priya@example.com']);

    expect(User::count())->toBe(0);
});

test('an existing account signs in without a country', function () {
    $user = User::factory()->create(['email' => 'priya@example.com']);

    $this->postJson(route('api.auth.google'), ['id_token' => googleToken()])->assertOk();

    $this->assertAuthenticatedAs($user, 'web');
});

test('tokens that are not good Google tokens are refused', function (Closure $token) {
    $this->postJson(route('api.auth.google'), ['id_token' => $token(), 'country' => 'IN'])->assertUnauthorized();

    expect(User::count())->toBe(0);
    $this->assertGuest('web');
})->with([
    'for another app' => fn () => googleToken(['aud' => 'someone-elses-app']),
    'not from Google' => fn () => googleToken(['iss' => 'https://evil.example.com']),
    'expired' => fn () => googleToken(['exp' => time() - 600, 'iat' => time() - 4200]),
    'email not verified' => fn () => googleToken(['email_verified' => false]),
    'signed with another key' => fn () => googleToken(key: openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA])),
    'not a token at all' => fn () => 'nonsense',
]);
