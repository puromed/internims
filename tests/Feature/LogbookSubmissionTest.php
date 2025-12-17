<?php

declare(strict_types=1);

use App\Models\Application;
use App\Models\Internship;
use App\Models\LogbookEntry;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Volt;

beforeEach(function () {
    Storage::fake('public');
});

it('submits a logbook into the faculty review workflow', function () {
    $student = User::factory()->create();

    Application::query()->create([
        'user_id' => $student->id,
        'status' => 'approved',
        'submitted_at' => now(),
    ]);

    Internship::factory()->create([
        'user_id' => $student->id,
        'status' => 'active',
    ]);

    $this->actingAs($student);

    Volt::test('logbooks.index')
        ->set('week_number', 1)
        ->set('entry_text', 'Did meaningful work this week.')
        ->set(
            'entry_file',
            UploadedFile::fake()->create('logsheet.pdf', 100, 'application/pdf'),
        )
        ->call('submit');

    $entry = LogbookEntry::query()
        ->where('user_id', $student->id)
        ->where('week_number', 1)
        ->firstOrFail();

    expect($entry->status)->toBe('pending_review')
        ->and($entry->supervisor_status)->toBe('pending');
});
