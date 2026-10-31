<?php

namespace App\Auth;

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Verifies a Google ID token from the mobile apps' native sign-in: the signature
 * against Google's published keys, then issuer, audience, expiry and a verified email.
 * See https://developers.google.com/identity/sign-in/web/backend-auth
 */
class GoogleIdTokenVerifier
{
    public const KEYS_URL = 'https://www.googleapis.com/oauth2/v3/certs';

    private const ISSUERS = ['accounts.google.com', 'https://accounts.google.com'];

    /**
     * @throws InvalidGoogleToken
     */
    public function verify(string $idToken): GoogleIdentity
    {
        try {
            $claims = (array) JWT::decode($idToken, JWK::parseKeySet($this->keys()));
        } catch (Throwable $e) {
            throw new InvalidGoogleToken('The Google token could not be verified.', previous: $e);
        }

        $audiences = array_filter([
            config('services.google.client_id'),
            config('services.google.android_client_id'),
            config('services.google.ios_client_id'),
        ]);

        if (! in_array($claims['iss'] ?? null, self::ISSUERS, true)) {
            throw new InvalidGoogleToken('The token was not issued by Google.');
        }
        if (! in_array($claims['aud'] ?? null, $audiences, true)) {
            throw new InvalidGoogleToken('The token was issued for a different app.');
        }
        if (($claims['email_verified'] ?? false) !== true || ! is_string($claims['email'] ?? null) || ! is_string($claims['sub'] ?? null)) {
            throw new InvalidGoogleToken('The Google account has no verified email address.');
        }

        return new GoogleIdentity($claims['sub'], $claims['email'], is_string($claims['name'] ?? null) ? $claims['name'] : null);
    }

    /**
     * Google's signing keys, cached for an hour; Google rotates them every few days.
     *
     * @return array<string, mixed>
     */
    private function keys(): array
    {
        return Cache::remember('google-id-token-keys', now()->addHour(), function () {
            /** @var array<string, mixed> */
            return Http::timeout(5)->retry(2, 200)->get(self::KEYS_URL)->throw()->json();
        });
    }
}
