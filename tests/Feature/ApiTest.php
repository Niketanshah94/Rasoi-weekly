<?php

use App\Models\User;

test('the API rejects requests without a token', function () {
    $this->getJson('/api/user')->assertUnauthorized();
});

test('the API accepts a Sanctum token', function () {
    $user = User::factory()->create();
    $token = $user->createToken('android')->plainTextToken;

    $this->withToken($token)
        ->getJson('/api/user')
        ->assertOk()
        ->assertJsonPath('id', $user->id);
});
