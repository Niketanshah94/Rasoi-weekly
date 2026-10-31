<?php

use App\Enums\HouseholdRole;
use App\Models\Household;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('people who have set up their household can visit the dashboard', function () {
    $user = User::factory()->create();
    Household::factory()->create()->addMember($user, HouseholdRole::Owner);
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('people who haven’t set up their household are sent to onboarding', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('dashboard'))->assertRedirect(route('onboarding'));
});
