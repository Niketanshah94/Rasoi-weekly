<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Closes the starter kit's password routes while temporary password sign-in is off
 * (config rasoi.password_login). The routes stay registered so the front end builds either way.
 */
class RequirePasswordLogin
{
    /**
     * Fortify and settings routes that only make sense with a password.
     */
    public const ROUTES = [
        'login.store',
        'register.store',
        'password.request',
        'password.email',
        'password.reset',
        'password.update',
        'password.confirm',
        'password.confirm.store',
        'password.confirmation',
        'security.edit',
        'user-password.update',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (! config('rasoi.password_login') && $request->routeIs(...self::ROUTES)) {
            abort(404);
        }

        return $next($request);
    }
}
