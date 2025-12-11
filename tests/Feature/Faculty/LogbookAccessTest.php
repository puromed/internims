<?php

use App\Models\Internship;
use App\Models\LogbookEntry;
use App\Models\User;

it('students cannot access faculty logbook reviewer routes', function (): void {
    // Default factory role is student and email is verified.
    $student = User::factory()->create();

    $this->actingAs($student);

    $logbook = LogbookEntry::create([
        'user_id' => $student->id,
        'week_number' => 1,
        'entry_text' => 'Test entry',
        'file_path' => null,
        'status' => 'pending_review',
        'supervisor_status' => 'pending',
        'supervisor_comment' => null,
        'reviewed_at' => null,
        'reviewed_by' => null,
        'ai_analysis_json' => null,
        'submitted_at' => now(),
    ]);

    $this->get(route('faculty.dashboard'))->assertForbidden();
    $this->get(route('faculty.logbooks.index'))->assertForbidden();
    $this->get(route('faculty.logbooks.show', $logbook))->assertForbidden();
});

it('faculty can access their logbook dashboard, queue, and entries', function (): void {
    $faculty = User::factory()->faculty()->create();
    $student = User::factory()->create(); // student with verified email

    // Link student to faculty via internship so policies and queries line up.
    Internship::factory()
        ->state([
            'user_id' => $student->id,
            'faculty_supervisor_id' => $faculty->id,
            'status' => 'active',
        ])
        ->create();

    $logbook = LogbookEntry::create([
        'user_id' => $student->id,
        'week_number' => 1,
        'entry_text' => 'Test entry',
        'file_path' => null,
        'status' => 'pending_review',
        'supervisor_status' => 'pending',
        'supervisor_comment' => null,
        'reviewed_at' => null,
        'reviewed_by' => null,
        'ai_analysis_json' => null,
        'submitted_at' => now(),
    ]);

    $this->actingAs($faculty);

    $this->get(route('faculty.dashboard'))->assertOk();
    $this->get(route('faculty.logbooks.index'))->assertOk();
    $this->get(route('faculty.logbooks.show', $logbook))->assertOk();
});
