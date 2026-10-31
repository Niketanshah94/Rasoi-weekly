<?php

use App\Http\Controllers\Api\GoogleTokenController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Native Google sign-in in the mobile apps. Runs with the web middleware so it also
// starts a web session for the app's web view; it is exempt from CSRF (bootstrap/app.php).
Route::post('/auth/google', GoogleTokenController::class)
    ->middleware(['web', 'throttle:10,1'])
    ->name('api.auth.google');
