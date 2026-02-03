<?php

use App\Models\User;

test('guests are redirected from the home page to the login page', function () {
    $response = $this->get(route('home'));

    $response->assertRedirect(route('login'));
});

test('authenticated users are redirected from the home page to the dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = $this->get(route('home'));

    $response->assertRedirect(route('dashboard'));
});
