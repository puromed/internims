<?php

declare(strict_types=1);

use App\Models\Internship;
use App\Models\User;
use Livewire\Volt\Volt;

it('shows pending internships in the admin assignments list', function () {
    $admin = User::factory()->admin()->create();
    $student = User::factory()->create(['name' => 'Placed Student']);

    Internship::factory()->create([
        'user_id' => $student->id,
        'status' => 'pending',
        'company_name' => 'Acme Sdn Bhd',
    ]);

    $this->actingAs($admin);

    Volt::test('admin.assignments.index')->assertSee('Placed Student');
});

it(
    'can auto-assign faculty supervisors to unassigned internships',
    function () {
        $admin = User::factory()->admin()->create();

        $facultyA = User::factory()->faculty()->create();
        $facultyB = User::factory()->faculty()->create();

        $studentOne = User::factory()->create();
        $studentTwo = User::factory()->create();
        $studentThree = User::factory()->create();

        Internship::factory()->create([
            'user_id' => $studentOne->id,
            'status' => 'pending',
            'faculty_supervisor_id' => null,
        ]);

        Internship::factory()->create([
            'user_id' => $studentTwo->id,
            'status' => 'active',
            'faculty_supervisor_id' => null,
        ]);

        Internship::factory()->create([
            'user_id' => $studentThree->id,
            'status' => 'pending',
            'faculty_supervisor_id' => null,
        ]);

        $this->actingAs($admin);

        Volt::test('admin.assignments.index')->call('autoAssign');

        $assignedFacultyIds = Internship::query()
            ->whereIn('status', ['pending', 'active'])
            ->pluck('faculty_supervisor_id')
            ->unique()
            ->filter()
            ->values();

        expect(
            Internship::query()
                ->whereIn('status', ['pending', 'active'])
                ->whereNull('faculty_supervisor_id')
                ->exists(),
        )->toBeFalse();

        expect($assignedFacultyIds->all())
            ->each()
            ->toBeIn([$facultyA->id, $facultyB->id]);
    },
);

it(
    'marks an internship as active when assigning a faculty supervisor',
    function () {
        $admin = User::factory()->admin()->create();
        $faculty = User::factory()->faculty()->create();
        $student = User::factory()->create();

        $internship = Internship::factory()->create([
            'user_id' => $student->id,
            'status' => 'pending',
            'faculty_supervisor_id' => null,
        ]);

        $this->actingAs($admin);

        Volt::test('admin.assignments.index')
            ->call('startEditing', $internship->id, null)
            ->set('selectedFacultyId', $faculty->id)
            ->call('assignFaculty');

        expect($internship->refresh()->status)
            ->toBe('active')
            ->and($internship->faculty_supervisor_id)
            ->toBe($faculty->id);
    },
);
