<?php

declare(strict_types=1);

use App\Models\EligibilityDoc;
use App\Models\User;
use App\Notifications\EligibilityDocSubmittedNotification;
use App\Notifications\EligibilityStatusNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Volt;

beforeEach(function () {
    Notification::fake();
    Storage::fake('public');
});

it('notifies admins when a student uploads an eligibility doc', function () {
    $admin = User::factory()->admin()->create();
    $student = User::factory()->create();

    $this->actingAs($student);

    Volt::test('eligibility.index')
        ->set('uploads.resume', UploadedFile::fake()->create('resume.pdf', 100, 'application/pdf'))
        ->call('uploadDoc', 'resume');

    expect(EligibilityDoc::query()
        ->where('user_id', $student->id)
        ->where('type', 'resume')
        ->exists())->toBeTrue();

    Notification::assertSentTo(
        $admin,
        EligibilityDocSubmittedNotification::class,
        function (EligibilityDocSubmittedNotification $notification, array $channels) use ($student, $admin) {
            expect($channels)->toContain('database')
                ->and($channels)->toContain('mail');

            $mail = $notification->toMail($admin);
            expect($mail)->toBeInstanceOf(MailMessage::class)
                ->and($mail->subject)->toContain('New eligibility document');

            return $notification->student->is($student)
                && $notification->doc->type === 'resume';
        }
    );
});

it('shows students with submitted docs in the admin eligibility queue', function () {
    $admin = User::factory()->admin()->create();
    $student = User::factory()->create(['name' => 'Student One']);

    EligibilityDoc::query()->create([
        'user_id' => $student->id,
        'type' => 'resume',
        'path' => 'eligibility/resume/resume.pdf',
        'status' => 'pending',
    ]);

    $this->actingAs($admin);

    Volt::test('admin.eligibility.index')
        ->assertSee('Student One');
});

it('approves a students eligibility and notifies them', function () {
    $admin = User::factory()->admin()->create();
    $student = User::factory()->create();

    foreach (['resume', 'transcript', 'offer_letter'] as $type) {
        EligibilityDoc::query()->create([
            'user_id' => $student->id,
            'type' => $type,
            'path' => "eligibility/{$type}/file.pdf",
            'status' => 'pending',
        ]);
    }

    $this->actingAs($admin);

    Volt::test('admin.eligibility.index')
        ->call('approve', $student->id);

    expect(EligibilityDoc::query()
        ->where('user_id', $student->id)
        ->whereIn('type', ['resume', 'transcript', 'offer_letter'])
        ->pluck('status')
        ->unique()
        ->all())->toBe(['approved']);

    Notification::assertSentTo(
        $student,
        EligibilityStatusNotification::class,
        function (EligibilityStatusNotification $notification, array $channels) use ($student) {
            expect($channels)->toContain('database')
                ->and($channels)->toContain('mail');

            return $notification->student->is($student)
                && $notification->status === 'approved';
        }
    );
});
