<?php

use App\Models\User;

test('registration requires student id and course', function () {
    $response = $this->from(route('register'))->post(route('register.store'), [
        'name' => 'Test Student',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasErrors(['student_id', 'course_code']);
});

test('students without profile completion are redirected to profile settings', function () {
    $user = User::factory()->create([
        'student_id' => null,
        'course_code' => null,
    ]);

    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('profile.edit'));
});

test('students with profile completion can access the dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertStatus(200);
});
