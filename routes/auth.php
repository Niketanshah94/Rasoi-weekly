<?php

use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\LoginLinkController;
use Illuminate\Support\Facades\Route;

/*
| Customer sign-in: Google or a one-time email link (spec §1). The log-in and
| sign-up pages themselves are Fortify's /login and /register views.
*/

Route::middleware('guest')->group(function () {
    Route::post('login/link', [LoginLinkController::class, 'sendLogin'])
        ->middleware('throttle:login-link')
        ->name('login-link.login');
    Route::post('register/link', [LoginLinkController::class, 'sendSignup'])
        ->middleware('throttle:login-link')
        ->name('login-link.signup');
    Route::get('login/check-inbox', [LoginLinkController::class, 'checkInbox'])->name('login-link.sent');

    Route::get('auth/google', [GoogleController::class, 'redirect'])->name('google.redirect');
    Route::get('auth/google/callback', [GoogleController::class, 'callback'])->name('google.callback');
});

// Open to signed-in visitors too, so a link opened on a shared device still works.
Route::get('login/link/{loginLink}', [LoginLinkController::class, 'show'])
    ->whereUuid('loginLink')
    ->name('login-link.show');
Route::post('login/link/{loginLink}', [LoginLinkController::class, 'consume'])
    ->whereUuid('loginLink')
    ->middleware('throttle:login-link')
    ->name('login-link.consume');
