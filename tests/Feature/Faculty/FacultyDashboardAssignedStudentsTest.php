<?php

declare(strict_types=1);

use App\Models\Internship;
use App\Models\User;
use Livewire\Volt\Volt;

it('shows assigned students on the faculty dashboard for pending internships', function (): void {
    $faculty = User::factory()->faculty()->create();
    $student = User::factory()->create(['name' => 'Assigned Student']);

    Internship::factory()->create([
        'user_id' => $student->id,
        'faculty_supervisor_id' => $faculty->id,
        'status' => 'pending',
    ]);

    $this->actingAs($faculty);

    Volt::test('faculty.dashboard')
        ->assertSee('Assigned Student')
        ->assertViewHas('activeInterns', 1);
});

it('shows assigned students on the faculty students page for pending internships', function (): void {
    $faculty = User::factory()->faculty()->create();
    $student = User::factory()->create(['name' => 'Assigned Student']);

    Internship::factory()->create([
        'user_id' => $student->id,
        'faculty_supervisor_id' => $faculty->id,
        'status' => 'pending',
    ]);

    $this->actingAs($faculty);

    Volt::test('faculty.students.index')->assertSee('Assigned Student');
});
