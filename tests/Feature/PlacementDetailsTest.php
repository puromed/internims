<?php

declare(strict_types=1);

use App\Models\Application;
use App\Models\Internship;
use App\Models\User;
use Livewire\Volt\Volt;

test('student sees placement confirmed status after confirming a company', function (): void {
    $student = User::factory()->create();

    $application = Application::create([
        'user_id' => $student->id,
        'status' => 'submitted',
        'submitted_at' => now(),
    ]);

    $approvedProposal = $application->proposedCompanies()->create([
        'name' => 'Approved Company',
        'website' => 'https://example.com',
        'address' => '123 Street',
        'job_scope' => 'Software engineering internship',
        'status' => 'approved',
        'admin_remarks' => null,
    ]);

    $this->actingAs($student);

    Volt::test('placement.index')
        ->call('confirmPlacement', $approvedProposal->id)
        ->assertSee('Placement Confirmed')
        ->assertSee('Internship Details');
});

test('student can save industry supervisor name after confirming placement', function (): void {
    $student = User::factory()->create();

    $application = Application::create([
        'user_id' => $student->id,
        'status' => 'submitted',
        'submitted_at' => now(),
    ]);

    $approvedProposal = $application->proposedCompanies()->create([
        'name' => 'Approved Company',
        'website' => 'https://example.com',
        'address' => '123 Street',
        'job_scope' => 'Software engineering internship',
        'status' => 'approved',
        'admin_remarks' => null,
    ]);

    $this->actingAs($student);

    Volt::test('placement.index')
        ->call('confirmPlacement', $approvedProposal->id)
        ->set('industrySupervisorName', 'Siti Nur Aisyah')
        ->call('saveInternshipDetails')
        ->assertHasNoErrors();

    expect(Internship::query()
        ->where('user_id', $student->id)
        ->value('supervisor_name'))->toBe('Siti Nur Aisyah');
});
