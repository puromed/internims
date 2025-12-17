<?php

use App\Models\Application;
use App\Models\Internship;
use App\Models\User;
use Livewire\Volt\Volt;

test('student can confirm an approved placement to create an internship', function (): void {
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
        ->assertSee('Confirm Placement')
        ->call('confirmPlacement', $approvedProposal->id)
        ->assertHasNoErrors();

    expect($application->refresh()->status)->toBe('approved');

    expect(Internship::query()
        ->where('user_id', $student->id)
        ->where('application_id', $application->id)
        ->where('company_name', 'Approved Company')
        ->exists())->toBeTrue();
});

test('student cannot confirm a non-approved proposal', function (): void {
    $student = User::factory()->create();

    $application = Application::create([
        'user_id' => $student->id,
        'status' => 'submitted',
        'submitted_at' => now(),
    ]);

    $pendingProposal = $application->proposedCompanies()->create([
        'name' => 'Pending Company',
        'website' => null,
        'address' => null,
        'job_scope' => 'Internship',
        'status' => 'pending',
        'admin_remarks' => null,
    ]);

    $this->actingAs($student);

    Volt::test('placement.index')
        ->call('confirmPlacement', $pendingProposal->id)
        ->assertHasErrors(['confirm']);

    expect($application->refresh()->status)->toBe('submitted');
    expect($application->internship()->exists())->toBeFalse();
});

test('student cannot confirm placement twice', function (): void {
    $student = User::factory()->create();

    $application = Application::create([
        'user_id' => $student->id,
        'status' => 'submitted',
        'submitted_at' => now(),
    ]);

    $approvedProposal = $application->proposedCompanies()->create([
        'name' => 'Approved Company',
        'website' => null,
        'address' => null,
        'job_scope' => 'Internship',
        'status' => 'approved',
        'admin_remarks' => null,
    ]);

    Internship::factory()->create([
        'user_id' => $student->id,
        'application_id' => $application->id,
        'company_name' => 'Approved Company',
    ]);

    $this->actingAs($student);

    Volt::test('placement.index')
        ->call('confirmPlacement', $approvedProposal->id)
        ->assertHasErrors(['confirm']);
});
