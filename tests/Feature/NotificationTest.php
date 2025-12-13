<?php

use App\Models\Internship;
use App\Models\LogbookEntry;
use App\Models\User;
use App\Notifications\LogbookEntryApprovedNotification;
use App\Notifications\LogbookEntryRevisionRequestedNotification;
use App\Notifications\NewLogbookSubmittedNotification;
use Illuminate\Support\Facades\Notification;
use Livewire\Volt\Volt;

beforeEach(function () {
    Notification::fake();
});

describe('Faculty Review Notifications', function () {
    it('sends notification when faculty approves logbook', function () {
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
            'status' => 'pending_review',
            'supervisor_status' => 'pending',
            'submitted_at' => now(),
        ]);

        $this->actingAs($faculty);

        Volt::test('faculty.logbooks.show', ['logbook' => $logbook])
            ->set('comment', 'Great work!')
            ->call('approve');

        Notification::assertSentTo(
            $student,
            LogbookEntryApprovedNotification::class,
            function ($notification) use ($logbook) {
                return $notification->entry->id === $logbook->id;
            }
        );
    });

    it('sends notification when faculty requests revision', function () {
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
            'status' => 'pending_review',
            'supervisor_status' => 'pending',
            'submitted_at' => now(),
        ]);

        $this->actingAs($faculty);

        Volt::test('faculty.logbooks.show', ['logbook' => $logbook])
            ->set('comment', 'Please add more details.')
            ->call('requestRevision');

        Notification::assertSentTo(
            $student,
            LogbookEntryRevisionRequestedNotification::class,
            function ($notification) use ($logbook) {
                return $notification->entry->id === $logbook->id;
            }
        );
    });

    it('does not send notification when revision request fails validation', function () {
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
            'status' => 'pending_review',
            'supervisor_status' => 'pending',
            'submitted_at' => now(),
        ]);

        $this->actingAs($faculty);

        Volt::test('faculty.logbooks.show', ['logbook' => $logbook])
            ->set('comment', '') // Empty comment should fail
            ->call('requestRevision')
            ->assertHasErrors(['comment']);

        Notification::assertNotSentTo($student, LogbookEntryRevisionRequestedNotification::class);
    });
});

describe('Notification Content', function () {
    it('approved notification contains correct data', function () {
        $student = User::factory()->create();
        $logbook = LogbookEntry::create([
            'user_id' => $student->id,
            'week_number' => 3,
            'entry_text' => 'Test entry',
            'status' => 'pending_review',
            'submitted_at' => now(),
        ]);

        $notification = new LogbookEntryApprovedNotification($logbook);
        $data = $notification->toDatabase($student);

        expect($data)->toHaveKey('entry_id', $logbook->id)
            ->toHaveKey('week', 3)
            ->toHaveKey('status', 'approved')
            ->toHaveKey('message');
    });

    it('revision notification contains supervisor comment', function () {
        $student = User::factory()->create();
        $logbook = LogbookEntry::create([
            'user_id' => $student->id,
            'week_number' => 4,
            'entry_text' => 'Test entry',
            'status' => 'pending_review',
            'supervisor_comment' => 'Please improve this section.',
            'submitted_at' => now(),
        ]);

        $notification = new LogbookEntryRevisionRequestedNotification($logbook);
        $data = $notification->toDatabase($student);

        expect($data)->toHaveKey('entry_id', $logbook->id)
            ->toHaveKey('week', 4)
            ->toHaveKey('status', 'revision_requested')
            ->toHaveKey('comment', 'Please improve this section.');
    });

    it('submission notification contains student name', function () {
        $student = User::factory()->create(['name' => 'John Doe']);
        $logbook = LogbookEntry::create([
            'user_id' => $student->id,
            'week_number' => 5,
            'entry_text' => 'Test entry',
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        $notification = new NewLogbookSubmittedNotification($logbook);
        $data = $notification->toDatabase($student);

        expect($data)->toHaveKey('entry_id', $logbook->id)
            ->toHaveKey('week', 5)
            ->toHaveKey('status', 'submitted')
            ->toHaveKey('student_name', 'John Doe');
    });
});
