<?php

use App\Models\Internship;
use App\Models\LogbookEntry;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component {
    public string $search = '';

    public function with(): array
    {
        $facultyId = Auth::id();

        $query = Internship::query()
            ->where('faculty_supervisor_id', $facultyId)
            ->where('status', 'active')
            ->with(['user', 'user.logbookEntries']);

        if ($this->search !== '') {
            $query->whereHas('user', fn($q) => $q->where('name', 'like', '%' . $this->search . '%'));
        }

        $internships = $query->get()->map(function ($internship) {
            $entries = $internship->user->logbookEntries ?? collect();
            
            return [
                'id' => $internship->id,
                'user' => $internship->user,
                'company' => $internship->company_name,
                'position' => $internship->position,
                'total_weeks' => $entries->count(),
                'approved' => $entries->where('supervisor_status', 'verified')->count(),
                'pending' => $entries->where('supervisor_status', 'pending')->count(),
                'revision' => $entries->where('supervisor_status', 'revision_requested')->count(),
                'latest_submission' => $entries->sortByDesc('submitted_at')->first()?->submitted_at,
            ];
        });

        return [
            'students' => $internships,
        ];
    }
}; ?>

<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <h2 class="text-2xl font-bold leading-7 text-gray-900 dark:text-gray-100 sm:text-3xl sm:tracking-tight">
                My Students
            </h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                View all students assigned to you and their logbook progress.
            </p>
        </div>

        {{-- Search --}}
        <div>
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Search student</label>
            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-slate-800 dark:text-gray-100 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                placeholder="Name..."
            />
        </div>
    </div>

    {{-- Students Grid --}}
    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        @forelse($students as $student)
            <div class="rounded-2xl bg-white dark:bg-slate-900/80 shadow-sm ring-1 ring-gray-200 dark:ring-white/10 p-6">
                {{-- Student Info --}}
                <div class="flex items-start gap-4 mb-4">
                    <div class="h-12 w-12 rounded-full bg-indigo-100 dark:bg-indigo-500/20 flex items-center justify-center">
                        <span class="text-lg font-semibold text-indigo-600 dark:text-indigo-400">
                            {{ substr($student['user']->name ?? 'S', 0, 1) }}
                        </span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate">
                            {{ $student['user']->name ?? 'Unknown Student' }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                            {{ $student['user']->email ?? '' }}
                        </p>
                        @if($student['company'])
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                <i data-lucide="building-2" class="inline h-3 w-3 mr-1"></i>
                                {{ $student['company'] }}
                            </p>
                        @endif
                    </div>
                </div>

                {{-- Progress Stats --}}
                <div class="grid grid-cols-3 gap-3 mb-4">
                    <div class="text-center p-2 rounded-lg bg-green-50 dark:bg-green-500/10">
                        <p class="text-lg font-bold text-green-600 dark:text-green-400">{{ $student['approved'] }}</p>
                        <p class="text-xs text-green-700 dark:text-green-300">Approved</p>
                    </div>
                    <div class="text-center p-2 rounded-lg bg-amber-50 dark:bg-amber-500/10">
                        <p class="text-lg font-bold text-amber-600 dark:text-amber-400">{{ $student['pending'] }}</p>
                        <p class="text-xs text-amber-700 dark:text-amber-300">Pending</p>
                    </div>
                    <div class="text-center p-2 rounded-lg bg-rose-50 dark:bg-rose-500/10">
                        <p class="text-lg font-bold text-rose-600 dark:text-rose-400">{{ $student['revision'] }}</p>
                        <p class="text-xs text-rose-700 dark:text-rose-300">Revision</p>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="flex items-center justify-between pt-4 border-t border-gray-100 dark:border-gray-700">
                    <div class="text-xs text-gray-500 dark:text-gray-400">
                        @if($student['latest_submission'])
                            Last: {{ $student['latest_submission']->diffForHumans() }}
                        @else
                            No submissions yet
                        @endif
                    </div>
                    <a
                        href="{{ route('faculty.logbooks.index', ['search' => $student['user']->name]) }}"
                        wire:navigate
                        class="inline-flex items-center text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-700"
                    >
                        View logbooks
                        <i data-lucide="arrow-right" class="h-3 w-3 ml-1"></i>
                    </a>
                </div>
            </div>
        @empty
            <div class="md:col-span-2 lg:col-span-3 rounded-2xl bg-white dark:bg-slate-900/80 shadow-sm ring-1 ring-gray-200 dark:ring-white/10 p-12 text-center">
                <i data-lucide="users" class="h-12 w-12 mx-auto text-gray-300 dark:text-gray-600 mb-4"></i>
                <p class="text-gray-500 dark:text-gray-400">No students assigned to you yet.</p>
            </div>
        @endforelse
    </div>
</div>
