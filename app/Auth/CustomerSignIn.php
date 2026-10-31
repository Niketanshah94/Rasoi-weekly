<?php

namespace App\Auth;

use App\Models\Country;
use App\Models\User;

/**
 * Finds or creates the customer account behind a sign-in. Google and email links
 * both prove the email address, so it is marked verified.
 */
class CustomerSignIn
{
    /**
     * The account for this Google identity, linking it to an existing account with the
     * same email. Returns null when there is no account and no country to create one in.
     */
    public function fromGoogle(GoogleIdentity $google, ?Country $country): ?User
    {
        $user = User::where('google_id', $google->id)->first()
            ?? User::where('email', $this->normalise($google->email))->first();

        if ($user) {
            $user->forceFill(['google_id' => $google->id, 'email_verified_at' => $user->email_verified_at ?? now()])->save();

            return $user;
        }

        if (! $country) {
            return null;
        }

        return $this->create($this->normalise($google->email), $google->name, $country, $google->id);
    }

    /**
     * The account for an email that has just been proven by a login link.
     */
    public function fromEmail(string $email, ?Country $country): ?User
    {
        $email = $this->normalise($email);
        $user = User::where('email', $email)->first();

        if ($user) {
            if (! $user->email_verified_at) {
                $user->forceFill(['email_verified_at' => now()])->save();
            }

            return $user;
        }

        return $country ? $this->create($email, null, $country) : null;
    }

    public function normalise(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    private function create(string $email, ?string $name, Country $country, ?string $googleId = null): User
    {
        $user = new User([
            // Onboarding asks for a name; Google supplies one to pre-fill.
            'name' => $name ?? '',
            'email' => $email,
            'google_id' => $googleId,
            'country_id' => $country->id,
        ]);
        $user->forceFill(['email_verified_at' => now()])->save();

        return $user;
    }
}
