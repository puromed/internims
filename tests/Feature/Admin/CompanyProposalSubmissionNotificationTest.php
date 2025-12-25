<?php

declare(strict_types=1);

use App\Models\Application;
use App\Models\ProposedCompany;
use App\Models\User;
use App\Notifications\ProposedCompanySubmittedNotification;
use Illuminate\Support\Facades\Notification;
use Livewire\Volt\Volt;

beforeEach(function () {
    Notification::fake();
});

it('notifies admins (database + mail) when a student submits company proposals', function () {
    $admin = User::factory()->admin()->create();
    $student = User::factory()->create();

    $this->actingAs($student);

    Volt::test('placement.index')
        ->set('proposals.0.name', 'Acme Sdn Bhd')
        ->set('proposals.0.job_scope', 'Backend intern work')
        ->set('proposals.1.name', 'Beta Sdn Bhd')
        ->set('proposals.1.job_scope', 'QA intern work')
        ->call('submit');

    Notification::assertSentToTimes($admin, ProposedCompanySubmittedNotification::class, 1);
});

it('does not send duplicate admin notifications on repeated submissions', function () {
    $admin = User::factory()->admin()->create();
    $student = User::factory()->create();

    $this->actingAs($student);

    Volt::test('placement.index')
        ->set('proposals.0.name', 'Acme Sdn Bhd')
        ->set('proposals.0.job_scope', 'Backend intern work')
        ->set('proposals.1.name', 'Beta Sdn Bhd')
        ->set('proposals.1.job_scope', 'QA intern work')
        ->call('submit')
        ->call('submit');

    Notification::assertSentToTimes($admin, ProposedCompanySubmittedNotification::class, 1);
});

it('groups proposals by student on the admin companies view', function () {
    $admin = User::factory()->admin()->create();
    $student = User::factory()->create();

    $application = Application::query()->create([
        'user_id' => $student->id,
        'status' => 'submitted',
        'submitted_at' => now(),
    ]);

    ProposedCompany::query()->create([
        'application_id' => $application->id,
        'name' => 'Acme Sdn Bhd',
        'website' => null,
        'address' => null,
        'job_scope' => 'Backend intern work',
        'status' => 'pending',
    ]);

    ProposedCompany::query()->create([
        'application_id' => $application->id,
        'name' => 'Beta Sdn Bhd',
        'website' => null,
        'address' => null,
        'job_scope' => 'QA intern work',
        'status' => 'pending',
    ]);

    $this->actingAs($admin);

    Volt::test('admin.companies.index')->assertViewHas('proposalGroups', function ($groups): bool {
        return $groups->count() === 1 && $groups->first()->count() === 2;
    });
});
