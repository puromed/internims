<?php

use App\Models\User;
use Livewire\Volt\Volt;

it('redirects an incomplete student profile to settings profile', function (): void {
    $student = User::factory()->create([
        'role' => 'student',
        'student_id' => null,
        'program_code' => null,
    ]);

    $this->actingAs($student)
        ->get(route('dashboard'))
        ->assertRedirect(route('profile.edit'));
});

it('allows an incomplete student to access settings profile', function (): void {
    $student = User::factory()->create([
        'role' => 'student',
        'student_id' => null,
        'program_code' => null,
    ]);

    $this->actingAs($student)
        ->get(route('profile.edit'))
        ->assertOk();
});

it('lets a student complete their profile and access the dashboard', function (): void {
    $student = User::factory()->create([
        'role' => 'student',
        'student_id' => null,
        'program_code' => null,
    ]);

    $this->actingAs($student);

    Volt::test('settings.profile')
        ->set('studentId', '2024123456')
        ->set('programCode', 'cs110')
        ->call('updateProfileInformation')
        ->assertHasNoErrors();

    $student->refresh();

    expect($student->student_id)->toBe('2024123456')
        ->and($student->program_code)->toBe('CS110');

    $this->get(route('dashboard'))->assertOk();
});

it('requires a 10 digit student id', function (): void {
    $student = User::factory()->create([
        'role' => 'student',
        'student_id' => null,
        'program_code' => null,
    ]);

    $this->actingAs($student);

    Volt::test('settings.profile')
        ->set('studentId', '202412345')
        ->set('programCode', 'CS110')
        ->call('updateProfileInformation')
        ->assertHasErrors(['studentId']);

    Volt::test('settings.profile')
        ->set('studentId', '202412345A')
        ->set('programCode', 'CS110')
        ->call('updateProfileInformation')
        ->assertHasErrors(['studentId']);
});

it('does not redirect admin users to complete student profile', function (): void {
    $admin = User::factory()->admin()->create([
        'student_id' => null,
        'program_code' => null,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk();
});
