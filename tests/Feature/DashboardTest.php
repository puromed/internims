<?php

use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertStatus(200);
});

test('dashboard shows dynamic activities', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // Create some activity
    $user->eligibilityDocs()->create([
        'type' => 'resume',
        'path' => 'resumes/test.pdf',
        'status' => 'approved',
    ]);

    $user->logbookEntries()->create([
        'week_number' => 1,
        'entry_text' => 'Test entry',
        'status' => 'submitted',
    ]);

    $response = $this->get(route('dashboard'));
    $response->assertStatus(200);
    $response->assertSee('Approved');
    $response->assertSee('Resume');
    $response->assertSee('Submitted');
    $response->assertSee('Week 1 Logbook');
});
