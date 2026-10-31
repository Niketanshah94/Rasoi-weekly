<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('with password sign-in off, every password route is closed', function () {
    config(['rasoi.password_login' => false]);
    $user = User::factory()->create();

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])->assertNotFound();
    $this->post(route('register.store'), [])->assertNotFound();
    $this->get(route('password.request'))->assertNotFound();
    $this->post(route('password.email'), ['email' => $user->email])->assertNotFound();
    $this->actingAs($user)->get(route('security.edit'))->assertNotFound();

    $this->assertAuthenticatedAs($user);
});

test('the sign-in pages offer a password only while it is on', function (bool $on) {
    config(['rasoi.password_login' => $on]);

    $this->get(route('login'))->assertInertia(fn (Assert $page) => $page->component('auth/Login')->where('passwordLogin', $on));
    $this->get(route('register'))->assertInertia(fn (Assert $page) => $page->component('auth/Register')->where('passwordLogin', $on));
})->with([true, false]);

test('an account without a password cannot log in with one', function () {
    $user = User::factory()->create(['password' => null]);

    $this->post(route('login.store'), ['email' => $user->email, 'password' => ''])->assertSessionHasErrors();
    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'anything'])->assertSessionHasErrors();

    $this->assertGuest();
});
