<?php

use App\Models\ImportantDate;
use App\Services\SemesterService;
use Carbon\Carbon;
use function Livewire\Volt\{state, computed, rules};

state([
    'title' => '',
    'date' => '',
    'type' => 'eligibility',
    'showModal' => false,
]);

rules([
    'title' => 'required|string|max:255',
    'date' => 'required|date',
    'type' => 'required|in:eligibility,placement,internship,other',
]);

$dates = computed(fn() => ImportantDate::latest('date')->get());

$save = function () {
    $this->validate();

    $semester = SemesterService::getSemesterCode(Carbon::parse($this->date));

    ImportantDate::create([
        'title' => $this->title,
        'date' => $this->date,
        'type' => $this->type,
        'semester' => $semester,
    ]);

    $this->reset(['title', 'date', 'type', 'showModal']);
    $this->dispatch('notify', 'Important date added successfully.');
};

$delete = function (ImportantDate $date) {
    $date->delete();
    $this->dispatch('notify', 'Important date deleted.');
};

?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Important Dates</flux:heading>
            <flux:subheading>Manage deadlines and important milestones for students.</flux:subheading>
        </div>
        <flux:button variant="primary" icon="plus" wire:click="$set('showModal', true)">Add Date</flux:button>
    </div>

    <div
        class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-zinc-900 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-zinc-800/50">
                    <tr>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                            Title</th>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                            Date</th>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                            Type</th>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                            Semester</th>
                        <th scope="col" class="relative px-6 py-3">
                            <span class="sr-only">Actions</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200 dark:bg-zinc-900 dark:divide-gray-700">
                    @foreach ($this->dates as $date)
                        <tr wire:key="{{ $date->id }}">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                {{ $date->title }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $date->date->format('M d, Y') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                <flux:badge size="sm"
                                    :color="$date->type === 'eligibility' ? 'blue' : ($date->type === 'placement' ? 'indigo' : 'gray')">
                                    {{ ucfirst($date->type) }}
                                </flux:badge>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $date->semester }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <flux:button variant="ghost" size="sm" icon="trash" wire:click="delete({{ $date->id }})"
                                    wire:confirm="Are you sure you want to delete this date?" />
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($this->dates->isEmpty())
            <div class="flex flex-col items-center justify-center p-12 text-center">
                <flux:icon name="calendar" class="size-12 text-gray-300 dark:text-gray-600" />
                <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">No dates set</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Get started by adding a new important date.</p>
            </div>
        @endif
    </div>

    <flux:modal name="add-date" wire:model="showModal" class="min-w-[24rem]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Add Important Date</flux:heading>
                <flux:subheading>Set a new deadline or milestone.</flux:subheading>
            </div>

            <flux:field>
                <flux:label>Title</flux:label>
                <flux:input wire:model="title" placeholder="e.g., Eligibility Document Deadline" />
                <flux:error name="title" />
            </flux:field>

            <flux:field>
                <flux:label>Date</flux:label>
                <flux:input type="date" wire:model="date" />
                <flux:error name="date" />
            </flux:field>

            <flux:field>
                <flux:label>Type</flux:label>
                <flux:select wire:model="type">
                    <flux:select.option value="eligibility">Eligibility</flux:select.option>
                    <flux:select.option value="placement">Placement</flux:select.option>
                    <flux:select.option value="internship">Internship</flux:select.option>
                    <flux:select.option value="other">Other</flux:select.option>
                </flux:select>
                <flux:error name="type" />
            </flux:field>

            @if($date && is_string($date) && strtotime($date))
                <div class="rounded-lg bg-gray-50 dark:bg-zinc-800 p-3">
                    <flux:label class="mb-1">Semester (auto-detected)</flux:label>
                    <div class="flex items-center gap-2">
                        <flux:badge color="indigo">
                            {{ \App\Services\SemesterService::getSemesterCode(\Carbon\Carbon::parse($date)) }}
                        </flux:badge>
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            Based on selected date
                        </span>
                    </div>
                </div>
            @endif

            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" wire:click="$set('showModal', false)">Cancel</flux:button>
                <flux:button variant="primary" wire:click="save">Save Date</flux:button>
            </div>
        </div>
    </flux:modal>
</div>