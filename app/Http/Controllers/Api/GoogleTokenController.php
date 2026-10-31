<?php

namespace App\Http\Controllers\Api;

use App\Auth\CustomerSignIn;
use App\Auth\GoogleIdTokenVerifier;
use App\Auth\InvalidGoogleToken;
use App\Http\Controllers\Controller;
use App\Models\Country;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * POST /api/auth/google: the mobile apps send the ID token from native Google sign-in
 * and get a Sanctum token back. A web session starts too, so the app's web view is signed in.
 */
class GoogleTokenController extends Controller
{
    public function __invoke(Request $request, GoogleIdTokenVerifier $verifier, CustomerSignIn $signIn): JsonResponse
    {
        $data = $request->validate([
            'id_token' => ['required', 'string', 'max:4096'],
            'country' => ['nullable', Rule::exists(Country::class, 'code')],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        try {
            $google = $verifier->verify($data['id_token']);
        } catch (InvalidGoogleToken $e) {
            report($e);

            return response()->json(['message' => 'Google sign-in could not be verified. Please try again.'], 401);
        }

        $country = isset($data['country']) ? Country::where('code', $data['country'])->first() : null;
        $user = $signIn->fromGoogle($google, $country);

        if (! $user) {
            // The app asks for a country, then calls again with it.
            return response()->json([
                'message' => 'Choose your country to create your account.',
                'needs_country' => true,
                'email' => $google->email,
            ], 422);
        }

        Auth::guard('web')->login($user, remember: true);
        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        return response()->json([
            'token' => $user->createToken($data['device_name'] ?? 'mobile')->plainTextToken,
            'user' => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email, 'country' => $user->country?->code],
        ]);
    }
}
