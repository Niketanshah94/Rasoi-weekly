<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;

/**
 * Reads trusted proxies from config at request time, so the setting
 * still applies when the config is cached.
 */
class TrustProxies extends Middleware
{
    /**
     * @return array<int, string>|string|null
     */
    protected function proxies()
    {
        $proxies = config('rasoi.trusted_proxies');

        if (! is_string($proxies) || $proxies === '') {
            return parent::proxies();
        }

        return $proxies === '*' ? '*' : array_map('trim', explode(',', $proxies));
    }
}
