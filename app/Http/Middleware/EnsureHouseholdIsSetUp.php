<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Customers finish onboarding before they reach the app.
 */
class EnsureHouseholdIsSetUp
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user('web')?->household()) {
            return to_route('onboarding');
        }

        return $next($request);
    }
}
