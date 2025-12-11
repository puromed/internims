<?php

use App\Models\User;

it('updates the theme preference via the appearance endpoint', function (): void {
    $user = User::factory()->create(['theme_preference' => 'system']);

    $response = $this
        ->actingAs($user)
        ->postJson(route('appearance.update'), ['theme' => 'dark']);

    $response
        ->assertOk()
        ->assertJson(['theme' => 'dark']);

    expect($user->refresh()->theme_preference)->toBe('dark');
});

it('rejects an invalid theme preference value', function (): void {
    $user = User::factory()->create(['theme_preference' => 'light']);

    $response = $this
        ->actingAs($user)
        ->postJson(route('appearance.update'), ['theme' => 'purple']);

    $response->assertUnprocessable();
    expect($user->refresh()->theme_preference)->toBe('light');
});
