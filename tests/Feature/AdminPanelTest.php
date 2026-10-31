<?php

use App\Models\Staff;
use App\Models\User;

test('the admin host shows the Filament login', function () {
    $this->get(adminUrl('/login'))
        ->assertOk()
        ->assertSee('Rasoi admin');
});

test('guests on the admin host are sent to the admin login', function () {
    $this->get(adminUrl('/'))->assertRedirect(adminUrl('/login'));
});

test('staff can open the admin dashboard', function () {
    $this->actingAs(Staff::factory()->create(), 'staff')
        ->get(adminUrl('/'))
        ->assertOk();
});

test('a signed-in customer cannot open the admin dashboard', function () {
    $this->actingAs(User::factory()->create(), 'web')
        ->get(adminUrl('/'))
        ->assertRedirect(adminUrl('/login'));
});

test('the customer login on the main host is not the admin login', function () {
    $this->get('/login')
        ->assertOk()
        ->assertDontSee('Rasoi admin');
});

test('only owners can open Horizon', function () {
    $this->get(adminUrl('/horizon'))->assertForbidden();

    $this->actingAs(Staff::factory()->create(), 'staff')
        ->get(adminUrl('/horizon'))
        ->assertForbidden();

    $this->actingAs(Staff::factory()->owner()->create(), 'staff')
        ->get(adminUrl('/horizon'))
        ->assertOk();
});

test('Horizon is not served on the customer host', function () {
    $this->actingAs(Staff::factory()->owner()->create(), 'staff')
        ->get('/horizon')
        ->assertNotFound();
});
