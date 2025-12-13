<?php

use App\Models\LogbookEntry;
use App\Models\Internship;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component {
    public int $pendingLogbooks = 0;
    public int $activeInterns = 0;

    public function mount(): void
    {
        $facultyId = Auth::id();

        // Logbooks to verify
        $this->pendingLogbooks = LogbookEntry::query()
            ->whereHas('user.internships', fn($q) => $q->where('faculty_supervisor_id', $facultyId))
            ->where('supervisor_status', 'pending')
            ->count();

        // Active interns
        $this->activeInterns = Internship::query()
            ->where('faculty_supervisor_id', $facultyId)
            ->where('status', 'active')
            ->count();
    }
}; ?>

<div class="py-10">
    <div class="px-4 sm:px-6 lg:px-8">

        {{-- Page Header --}}
        <div class="md:flex md:items-center md:justify-between mb-8">
            <div class="min-w-0 flex-1">
                <h2 class="text-2xl font-bold leading-7 text-gray-900 dark:text-gray-100 sm:truncate sm:text-3xl sm:tracking-tight">
                    Welcome back, {{ Auth::user()->name }}
                </h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Here's an overview of your internship supervision activities.</p>
            </div>
        </div>

        {{-- Stats Grid --}}
        <dl class="grid grid-cols-1 gap-5 sm:grid-cols-2 mb-8">
            {{-- Logbooks to Verify --}}
            <div class="overflow-hidden rounded-2xl bg-white dark:bg-slate-900/80 px-4 py-5 shadow-sm ring-1 ring-gray-900/5 dark:ring-white/10 sm:p-6">
                <dt class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">Logbooks to Verify</dt>
                <dd class="mt-1 flex items-baseline justify-between md:block lg:flex">
                    <div class="flex items-baseline text-2xl font-semibold text-gray-900 dark:text-gray-100">
                        {{ $pendingLogbooks }}
                        <span class="ml-2 text-sm font-medium text-gray-500 dark:text-gray-400">pending</span>
                    </div>
                </dd>
            </div>

            {{-- Active Interns --}}
            <div class="overflow-hidden rounded-2xl bg-white dark:bg-slate-900/80 px-4 py-5 shadow-sm ring-1 ring-gray-900/5 dark:ring-white/10 sm:p-6">
                <dt class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">Active Interns</dt>
                <dd class="mt-1 flex items-baseline justify-between md:block lg:flex">
                    <div class="flex items-baseline text-2xl font-semibold text-indigo-600 dark:text-indigo-400">
                        {{ $activeInterns }}
                        <span class="ml-2 text-sm font-medium text-gray-500 dark:text-gray-400">students</span>
                    </div>
                </dd>
            </div>
        </dl>

        {{-- Quick Actions --}}
        <div class="rounded-2xl bg-white dark:bg-slate-900/80 shadow-sm ring-1 ring-gray-900/5 dark:ring-white/10 p-6">
            <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-4">Quick Actions</h3>
            <div class="flex flex-wrap gap-4">
                <a href="{{ route('faculty.logbooks.index') }}" wire:navigate class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-500 dark:bg-indigo-500 dark:hover:bg-indigo-600">
                    <i data-lucide="book-open" class="mr-2 h-4 w-4"></i>
                    Review Logbooks
                </a>
                <a href="{{ route('faculty.students.index') }}" wire:navigate class="inline-flex items-center rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-slate-800 px-4 py-2.5 text-sm font-semibold text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-slate-700">
                    <i data-lucide="users" class="mr-2 h-4 w-4"></i>
                    View Students
                </a>
            </div>
        </div>

    </div>
</div>

