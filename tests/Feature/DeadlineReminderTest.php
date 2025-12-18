<?php

use App\Models\ImportantDate;
use App\Models\User;
use App\Notifications\DeadlineReminderNotification;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;

test('reminders are sent to students with pending eligibility', function () {
    Notification::fake();

    $student = User::factory()->create(['role' => 'student']);

    // Create a deadline 3 days from now
    ImportantDate::create([
        'title' => 'Eligibility Deadline',
        'date' => now()->addDays(3)->toDateString(),
        'type' => 'eligibility',
        'semester' => 'Spring 2025',
    ]);

    Artisan::call('app:send-deadline-reminders');

    Notification::assertSentTo(
        $student,
        DeadlineReminderNotification::class,
        function ($notification) {
            return $notification->reminderType === 'eligibility';
        }
    );
});

test('reminders are sent to students without placement', function () {
    Notification::fake();

    $student = User::factory()->create(['role' => 'student']);

    // Create a deadline for today
    ImportantDate::create([
        'title' => 'Placement Deadline',
        'date' => now()->toDateString(),
        'type' => 'placement',
        'semester' => 'Spring 2025',
    ]);

    Artisan::call('app:send-deadline-reminders');

    Notification::assertSentTo(
        $student,
        DeadlineReminderNotification::class,
        function ($notification) {
            return $notification->reminderType === 'placement';
        }
    );
});

test('reminders are not sent if deadline is far away', function () {
    Notification::fake();

    $student = User::factory()->create(['role' => 'student']);

    ImportantDate::create([
        'title' => 'Future Deadline',
        'date' => now()->addDays(10)->toDateString(),
        'type' => 'eligibility',
        'semester' => 'Spring 2025',
    ]);

    Artisan::call('app:send-deadline-reminders');

    Notification::assertNotSentTo($student, DeadlineReminderNotification::class);
});
