<?php

namespace App\Auth;

/**
 * Who Google says signed in: from a verified ID token or the web OAuth flow.
 */
final readonly class GoogleIdentity
{
    public function __construct(
        public string $id,
        public string $email,
        public ?string $name,
    ) {}
}
