<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Volt\Volt;

it('allows an admin to create a faculty user', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    Volt::test('admin.users.index')
        ->call('openCreateUserModal')
        ->set('createName', 'New Faculty')
        ->set('createEmail', 'newfaculty@example.com')
        ->set('createRole', 'faculty')
        ->set('createPassword', 'Password123!')
        ->set('createPasswordConfirmation', 'Password123!')
        ->call('createUser')
        ->assertHasNoErrors();

    $created = User::query()->where('email', 'newfaculty@example.com')->firstOrFail();

    expect($created->role)->toBe('faculty')
        ->and($created->email_verified_at)->not->toBeNull()
        ->and(Hash::check('Password123!', $created->password))->toBeTrue();
});

it('prevents creating a student user from admin user management', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    Volt::test('admin.users.index')
        ->call('openCreateUserModal')
        ->set('createName', 'New Student')
        ->set('createEmail', 'newstudent@example.com')
        ->set('createRole', 'student')
        ->set('createPassword', 'Password123!')
        ->set('createPasswordConfirmation', 'Password123!')
        ->call('createUser')
        ->assertHasErrors(['createRole']);

    expect(User::query()->where('email', 'newstudent@example.com')->exists())->toBeFalse();
});

it('validates unique email when creating a user', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->faculty()->create(['email' => 'dupe@example.com']);

    $this->actingAs($admin);

    Volt::test('admin.users.index')
        ->call('openCreateUserModal')
        ->set('createName', 'Duped Faculty')
        ->set('createEmail', 'dupe@example.com')
        ->set('createRole', 'faculty')
        ->set('createPassword', 'Password123!')
        ->set('createPasswordConfirmation', 'Password123!')
        ->call('createUser')
        ->assertHasErrors(['createEmail']);
});
