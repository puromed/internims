<?php

use App\Models\LogbookEntry;
use App\Models\Internship;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component {
    public function with(): array
    {
        $facultyId = Auth::id();
        $assignedStatuses = ["pending", "active"];

        // Pending Logbooks Count
        $pendingLogbooks = LogbookEntry::query()
            ->whereHas(
                "user.internships",
                fn($q) => $q->where("faculty_supervisor_id", $facultyId),
            )
            ->where("supervisor_status", "pending")
            ->count();

        // Assigned Students Count
        $activeInterns = Internship::query()
            ->where("faculty_supervisor_id", $facultyId)
            ->whereIn("status", $assignedStatuses)
            ->count();

        // Students with Revisions (Count of students who have at least one logbook in 'revision_requested')
        $studentsOnRevision = Internship::query()
            ->where("faculty_supervisor_id", $facultyId)
            ->whereIn("status", $assignedStatuses)
            ->whereHas(
                "user.logbookEntries",
                fn($q) => $q->where("supervisor_status", "revision_requested"),
            )
            ->count();

        // Detailed Student List (reusing logic from students/index)
        $students = Internship::query()
            ->where("faculty_supervisor_id", $facultyId)
            ->whereIn("status", $assignedStatuses)
            ->with(["user", "user.logbookEntries"])
            ->get()
            ->map(function ($internship) {
                $entries = $internship->user->logbookEntries ?? collect();
                return [
                    "id" => $internship->id,
                    "user" => $internship->user,
                    "company" => $internship->company_name,
                    "position" => $internship->position,
                    "total_weeks" => $entries->count(),
                    "approved" => $entries
                        ->where("supervisor_status", "verified")
                        ->count(),
                    "pending" => $entries
                        ->where("supervisor_status", "pending")
                        ->count(),
                    "revision" => $entries
                        ->where("supervisor_status", "revision_requested")
                        ->count(),
                    "latest_submission" => $entries
                        ->sortByDesc("submitted_at")
                        ->first()?->submitted_at,
                ];
            });

        return [
            "pendingLogbooks" => $pendingLogbooks,
            "activeInterns" => $activeInterns,
            "studentsOnRevision" => $studentsOnRevision,
            "students" => $students,
        ];
    }
};
?>

<div class="space-y-6">
    {{-- Header --}}
    <div class="border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-slate-900 px-4 sm:px-6 lg:px-8 -mx-4 sm:-mx-6 lg:-mx-8 -mt-6 pt-6 pb-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Faculty</p>
                <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Dashboard</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">Overview of your assigned students and their progress.</p>
            </div>
        </div>
    </div>

    {{-- Stats Grid --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        {{-- Pending Logbooks --}}
        <div class="flex flex-col justify-between rounded-xl border border-amber-200 bg-amber-50/30 p-5 shadow-sm dark:border-amber-500/20 dark:bg-amber-500/5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-amber-600 dark:text-amber-500">Logbooks to Verify</p>
                    <p class="text-3xl font-bold text-amber-700 dark:text-amber-500">{{ $pendingLogbooks }}</p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-500/20">
                    <flux:icon name="book-open" class="size-5 text-amber-600 dark:text-amber-500" />
                </div>
            </div>
            <div class="mt-4">
                <flux:button variant="ghost" size="sm" href="{{ route('faculty.logbooks.index') }}" icon-trailing="arrow-right" class="w-full justify-between text-amber-700 hover:bg-amber-100 dark:text-amber-500 dark:hover:bg-amber-500/20">
                    Review pending
                </flux:button>
            </div>
        </div>

        {{-- Active Interns --}}
        <div class="flex flex-col justify-between rounded-xl border border-indigo-200 bg-indigo-50/30 p-5 shadow-sm dark:border-indigo-500/20 dark:bg-indigo-500/5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-indigo-600 dark:text-indigo-500">Assigned Students</p>
                    <p class="text-3xl font-bold text-indigo-700 dark:text-indigo-500">{{ $activeInterns }}</p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-indigo-100 dark:bg-indigo-500/20">
                    <flux:icon name="users" class="size-5 text-indigo-600 dark:text-indigo-500" />
                </div>
            </div>
            <div class="mt-4">
                <flux:button variant="ghost" size="sm" href="{{ route('faculty.students.index') }}" icon-trailing="arrow-right" class="w-full justify-between text-indigo-700 hover:bg-indigo-100 dark:text-indigo-500 dark:hover:bg-indigo-500/20">
                    View all students
                </flux:button>
            </div>
        </div>

        {{-- Students on Revision --}}
        <div class="flex flex-col justify-between rounded-xl border border-rose-200 bg-rose-50/30 p-5 shadow-sm dark:border-rose-500/20 dark:bg-rose-500/5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-rose-600 dark:text-rose-500">Needs Revision</p>
                    <p class="text-3xl font-bold text-rose-700 dark:text-rose-500">{{ $studentsOnRevision }}</p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-rose-100 dark:bg-rose-500/20">
                    <flux:icon name="pencil" class="size-5 text-rose-600 dark:text-rose-500" />
                </div>
            </div>
            <div class="mt-4 flex items-center gap-2 text-xs font-medium text-rose-700 dark:text-rose-500">
                <span>Students with requested changes</span>
            </div>
        </div>
    </div>

    {{-- Assigned Students List --}}
    <div class="space-y-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Assigned Students</h3>

        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
             @forelse($students as $student)
                <div class="bg-white dark:bg-slate-900/80 rounded-xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm hover:shadow-md transition-all">
                    {{-- Student Header --}}
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex items-center gap-3">
                             <div class="h-10 w-10 rounded-full bg-indigo-100 dark:bg-indigo-500/20 flex items-center justify-center">
                                <span class="text-sm font-bold text-indigo-600 dark:text-indigo-400">
                                    {{ substr($student['user']->name ?? 'S', 0, 1) }}
                                </span>
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate max-w-[150px]">
                                    {{ $student['user']->name ?? 'Unknown' }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 truncate max-w-[150px]">
                                    {{ $student['company'] ?? 'No Company' }}
                                </p>
                            </div>
                        </div>
                         <flux:button size="xs" href="{{ route('faculty.logbooks.index', ['search' => $student['user']->name]) }}" icon="book-open" variant="subtle">
                            Review
                        </flux:button>
                    </div>

                    {{-- Mini Stats --}}
                    <div class="grid grid-cols-3 gap-2 py-3 border-t border-b border-gray-100 dark:border-gray-700/50 mb-3">
                        <div class="text-center">
                            <span class="block text-lg font-bold text-green-600 dark:text-green-500">{{ $student['approved'] }}</span>
                            <span class="block text-[10px] text-gray-500 uppercase tracking-wide">Done</span>
                        </div>
                        <div class="text-center border-l border-gray-100 dark:border-gray-700/50">
                            <span class="block text-lg font-bold text-amber-600 dark:text-amber-500">{{ $student['pending'] }}</span>
                             <span class="block text-[10px] text-gray-500 uppercase tracking-wide">Pending</span>
                        </div>
                        <div class="text-center border-l border-gray-100 dark:border-gray-700/50">
                            <span class="block text-lg font-bold text-rose-600 dark:text-rose-500">{{ $student['revision'] }}</span>
                             <span class="block text-[10px] text-gray-500 uppercase tracking-wide">Fix</span>
                        </div>
                    </div>

                    {{-- Footer --}}
                    <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                        <span>
                            @if($student['latest_submission'])
                                Latest: {{ $student['latest_submission']->diffForHumans(short: true) }}
                            @else
                                No submissions
                            @endif
                        </span>
                        @if($student['pending'] > 0)
                            <span class="inline-flex items-center gap-1 text-amber-600 dark:text-amber-500 font-medium">
                                <span class="h-1.5 w-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                Needs Review
                            </span>
                        @endif
                    </div>
                </div>
            @empty
                <div class="col-span-full rounded-xl border border-gray-200 bg-white p-12 text-center dark:border-gray-700 dark:bg-zinc-900">
                    <flux:icon name="users" class="mx-auto size-12 text-zinc-300" />
                    <p class="mt-4 text-sm font-medium text-zinc-500">No students assigned to you yet.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
