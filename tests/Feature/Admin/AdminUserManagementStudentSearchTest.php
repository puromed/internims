<?php

declare(strict_types=1);

use App\Models\User;
use Livewire\Volt\Volt;

it('shows student id and program code in admin users table', function (): void {
    $admin = User::factory()->admin()->create();

    User::factory()->create([
        'role' => 'student',
        'name' => 'Test Student',
        'email' => 'student@example.com',
        'student_id' => '2024123456',
        'program_code' => 'CS110',
    ]);

    $this->actingAs($admin);

    Volt::test('admin.users.index')
        ->set('roleFilter', 'student')
        ->assertSee('Student ID')
        ->assertSee('2024123456')
        ->assertSee('CS110');
});

it('can search students by student id and program code', function (): void {
    $admin = User::factory()->admin()->create();

    $alice = User::factory()->create([
        'role' => 'student',
        'name' => 'Alice Student',
        'email' => 'alice@student.example',
        'student_id' => '2024000001',
        'program_code' => 'CS110',
    ]);

    User::factory()->create([
        'role' => 'student',
        'name' => 'Bob Student',
        'email' => 'bob@student.example',
        'student_id' => '2024000002',
        'program_code' => 'IT230',
    ]);

    $this->actingAs($admin);

    Volt::test('admin.users.index')
        ->set('roleFilter', 'student')
        ->set('search', '2024000002')
        ->assertSee('bob@student.example')
        ->assertDontSee('alice@student.example');

    Volt::test('admin.users.index')
        ->set('roleFilter', 'student')
        ->set('search', 'CS110')
        ->assertSee('alice@student.example')
        ->assertDontSee('bob@student.example');

    Volt::test('admin.users.index')
        ->set('roleFilter', 'student')
        ->set('search', 'id:'.$alice->id)
        ->assertSee('alice@student.example')
        ->assertDontSee('bob@student.example');
});
