<?php

use App\Models\Application;
use App\Models\ProposedCompany;
use App\Models\User;
use App\Notifications\ProposedCompanyStatusNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Livewire\Volt\Volt;

beforeEach(function () {
    Notification::fake();
});

it('notifies student (database + mail) when admin approves proposal', function () {
    $admin = User::factory()->admin()->create();
    $student = User::factory()->create();

    $application = Application::query()->create([
        'user_id' => $student->id,
        'status' => 'submitted',
        'submitted_at' => now(),
    ]);

    $proposal = ProposedCompany::query()->create([
        'application_id' => $application->id,
        'name' => 'Acme Sdn Bhd',
        'website' => 'https://example.com',
        'address' => 'KL',
        'job_scope' => 'Backend intern work',
        'status' => 'pending',
    ]);

    $this->actingAs($admin);

    Volt::test('admin.companies.index')
        ->call('approve', $proposal->id);

    Notification::assertSentTo(
        $student,
        ProposedCompanyStatusNotification::class,
        function (ProposedCompanyStatusNotification $notification, array $channels) use ($proposal, $student) {
            expect($channels)->toContain('database')
                ->and($channels)->toContain('mail');

            $data = $notification->toDatabase($student);

            $mail = $notification->toMail($student);
            expect($mail)->toBeInstanceOf(MailMessage::class)
                ->and($mail->subject)->toContain('Approved');

            return $data['proposal_id'] === $proposal->id
                && $data['status'] === 'approved';
            
        }
    );
});

it('notifies student (database + mail) with remarks when admin rejects proposal', function () {
    $admin = User::factory()->admin()->create();
    $student = User::factory()->create();

    $application = Application::query()->create([
        'user_id' => $student->id,
        'status' => 'submitted',
        'submitted_at' => now(),
    ]);

    $proposal = ProposedCompany::query()->create([
        'application_id' => $application->id,
        'name' => 'Bad Fit Co',
        'website' => null,
        'address' => null,
        'job_scope' => 'Not relevant',
        'status' => 'pending',
    ]);

    $this->actingAs($admin);

    Volt::test('admin.companies.index')
        ->call('openRejectModal', $proposal->id)
        ->set('adminRemarks', 'Company does not align with program')
        ->call('confirmReject');

    Notification::assertSentTo(
        $student,
        ProposedCompanyStatusNotification::class,
        function (ProposedCompanyStatusNotification $notification, array $channels) use ($proposal, $student) {
            expect($channels)->toContain('database')
                ->and($channels)->toContain('mail');

            $data = $notification->toDatabase($student);

            $mail = $notification->toMail($student);
            expect($mail)->toBeInstanceOf(MailMessage::class)
                ->and($mail->subject)->toContain('Rejected');

            return $data['proposal_id'] === $proposal->id
                && $data['status'] === 'rejected'
                && ($data['comment'] ?? null) === 'Company does not align with program';
        }
    );
});

it('does not send duplicate notifications on repeated approve', function () {
    $admin = User::factory()->admin()->create();
    $student = User::factory()->create();

    $application = Application::query()->create([
        'user_id' => $student->id,
        'status' => 'submitted',
        'submitted_at' => now(),
    ]);

    $proposal = ProposedCompany::query()->create([
        'application_id' => $application->id,
        'name' => 'Acme Sdn Bhd',
        'website' => 'https://example.com',
        'address' => 'KL',
        'job_scope' => 'Backend intern work',
        'status' => 'pending',
    ]);

    $this->actingAs($admin);

    Volt::test('admin.companies.index')->call('approve', $proposal->id);
    Volt::test('admin.companies.index')->call('approve', $proposal->id);

    Notification::assertSentToTimes($student, ProposedCompanyStatusNotification::class, 1);
});
