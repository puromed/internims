<?php

declare(strict_types=1);

use App\Models\EligibilityDoc;
use App\Models\User;
use Livewire\Volt\Volt;

it('shows the correct pending eligibility count on the admin dashboard', function () {
    $admin = User::factory()->admin()->create();

    $pendingStudent = User::factory()->create(['name' => 'Pending Student']);
    EligibilityDoc::query()->create([
        'user_id' => $pendingStudent->id,
        'type' => 'resume',
        'path' => 'eligibility/resume/resume.pdf',
        'status' => 'pending',
    ]);

    $approvedStudent = User::factory()->create(['name' => 'Approved Student']);
    foreach (['resume', 'transcript', 'offer_letter'] as $type) {
        EligibilityDoc::query()->create([
            'user_id' => $approvedStudent->id,
            'type' => $type,
            'path' => "eligibility/{$type}/file.pdf",
            'status' => 'approved',
            'reviewed_at' => now(),
        ]);
    }

    $this->actingAs($admin);

    Volt::test('admin.dashboard')
        ->assertViewHas('pendingEligibility', 1);
});
