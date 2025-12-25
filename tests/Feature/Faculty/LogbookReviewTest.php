<?php

use App\Models\Internship;
use App\Models\LogbookEntry;
use App\Models\User;
use Livewire\Volt\Volt;

it('faculty can approve a logbook entry', function (): void {
    $faculty = User::factory()->faculty()->create();
    $student = User::factory()->create();

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

    Volt::test('faculty.logbooks.show', ['logbook' => $logbook])
        ->set('comment', 'Great work!')
        ->call('approve')
        ->assertHasNoErrors();

    $logbook->refresh();

    expect($logbook->supervisor_status)->toBe('verified');
    expect($logbook->status)->toBe('approved');
    expect($logbook->supervisor_comment)->toBe('Great work!');
    expect($logbook->reviewed_by)->toBe($faculty->id);
    expect($logbook->reviewed_at)->not->toBeNull();
});

it(
    'faculty can approve a submitted logbook entry (legacy status)',
    function (): void {
        $faculty = User::factory()->faculty()->create();
        $student = User::factory()->create();

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
            'status' => 'submitted',
            'supervisor_status' => 'pending',
            'supervisor_comment' => null,
            'reviewed_at' => null,
            'reviewed_by' => null,
            'ai_analysis_json' => null,
            'submitted_at' => now(),
        ]);

        $this->actingAs($faculty);

        Volt::test('faculty.logbooks.show', ['logbook' => $logbook])
            ->call('approve')
            ->assertHasNoErrors();
    },
);

it('faculty can request revision with required comment', function (): void {
    $faculty = User::factory()->faculty()->create();
    $student = User::factory()->create();

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

    Volt::test('faculty.logbooks.show', ['logbook' => $logbook])
        ->set(
            'comment',
            'Please provide more details about your daily activities.',
        )
        ->call('requestRevision')
        ->assertHasNoErrors();

    $logbook->refresh();

    expect($logbook->supervisor_status)->toBe('revision_requested');
    expect($logbook->status)->toBe('submitted');
    expect($logbook->supervisor_comment)->toBe(
        'Please provide more details about your daily activities.',
    );
    expect($logbook->reviewed_by)->toBe($faculty->id);
    expect($logbook->reviewed_at)->not->toBeNull();
});

it('faculty cannot request revision without comment', function (): void {
    $faculty = User::factory()->faculty()->create();
    $student = User::factory()->create();

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

    Volt::test('faculty.logbooks.show', ['logbook' => $logbook])
        ->set('comment', '')
        ->call('requestRevision')
        ->assertHasErrors(['comment']);

    $logbook->refresh();

    expect($logbook->supervisor_status)->toBe('pending');
    expect($logbook->status)->toBe('pending_review');
});
