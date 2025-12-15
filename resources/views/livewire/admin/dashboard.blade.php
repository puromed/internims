<?php

use App\Models\User;
use App\Models\Internship;
use App\Models\Application;
use App\Models\LogbookEntry;
use Livewire\Volt\Component;

new class extends Component {
    public function with(): array
    {
        return [
            'totalStudents' => User::where('role', 'student')->count(),
            'totalFaculty' => User::where('role', 'faculty')->count(),
            'totalAdmins' => User::where('role', 'admin')->count(),
            'pendingEligibility' => Application::where('eligibility_status', 'pending')->count(),
            'activeInternships' => Internship::where('status', 'active')->count(),
            'pendingLogbooks' => LogbookEntry::where('supervisor_status', 'pending')->count(),
        ];
    }
}; ?>

<div class="space-y-6">
    {{-- Header --}}
    <div class="border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-slate-900 px-4 sm:px-6 lg:px-8 -mx-4 sm:-mx-6 lg:-mx-8 -mt-6 pt-6 pb-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Admin</p>
                <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Dashboard</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">Overview of the internship management system.</p>
            </div>
        </div>
    </div>

    {{-- Stats Grid --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        {{-- Users Stats --}}
        <div class="flex flex-col gap-4 rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Users</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ $totalStudents + $totalFaculty + $totalAdmins }}</p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-indigo-50 dark:bg-indigo-500/10">
                    <flux:icon name="users" class="size-5 text-indigo-600 dark:text-indigo-400" />
                </div>
            </div>
            
            <div class="grid grid-cols-3 gap-2 border-t border-gray-100 pt-4 dark:border-gray-800">
                <div class="text-center">
                    <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $totalStudents }}</p>
                    <p class="text-xs text-gray-500">Students</p>
                </div>
                <div class="border-l border-gray-100 dark:border-gray-800 text-center">
                    <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $totalFaculty }}</p>
                    <p class="text-xs text-gray-500">Faculty</p>
                </div>
                <div class="border-l border-gray-100 dark:border-gray-800 text-center">
                    <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $totalAdmins }}</p>
                    <p class="text-xs text-gray-500">Admins</p>
                </div>
            </div>
        </div>

        {{-- Eligibility Stats --}}
        <div class="flex flex-col justify-between rounded-xl border border-amber-200 bg-amber-50/30 p-5 shadow-sm dark:border-amber-500/20 dark:bg-amber-500/5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-amber-600 dark:text-amber-500">Pending Eligibility</p>
                    <p class="text-3xl font-bold text-amber-700 dark:text-amber-500">{{ $pendingEligibility }}</p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-500/20">
                    <flux:icon name="document-text" class="size-5 text-amber-600 dark:text-amber-500" />
                </div>
            </div>
            <div class="mt-4">
                <flux:button variant="ghost" size="sm" href="{{ route('admin.eligibility.index') }}" icon-trailing="arrow-right" class="w-full justify-between text-amber-700 hover:bg-amber-100 dark:text-amber-500 dark:hover:bg-amber-500/20">
                    Review applications
                </flux:button>
            </div>
        </div>

        {{-- Internships Stats --}}
        <div class="flex flex-col justify-between rounded-xl border border-emerald-200 bg-emerald-50/30 p-5 shadow-sm dark:border-emerald-500/20 dark:bg-emerald-500/5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-emerald-600 dark:text-emerald-500">Active Internships</p>
                    <p class="text-3xl font-bold text-emerald-700 dark:text-emerald-500">{{ $activeInternships }}</p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-500/20">
                    <flux:icon name="briefcase" class="size-5 text-emerald-600 dark:text-emerald-500" />
                </div>
            </div>
            <div class="mt-4 flex items-center gap-2 text-xs font-medium text-emerald-700 dark:text-emerald-500">
                <flux:icon name="book-open" class="size-4" />
                <span>{{ $pendingLogbooks }} logbooks pending review</span>
            </div>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-zinc-900">
        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-4">Quick Actions</h3>
        <div class="flex flex-wrap gap-3">
            <flux:button href="{{ route('admin.eligibility.index') }}" variant="primary" icon="document-check">Review Eligibility</flux:button>
            <flux:button href="{{ route('admin.users.index') }}" icon="users">Manage Users</flux:button>
            <flux:button href="{{ route('admin.assignments.index') }}" icon="user-plus">Faculty Assignments</flux:button>
        </div>
    </div>
</div>
