<?php

use App\Models\LogbookEntry;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public ?string $status = 'pending'; // supervisor_status filter (pending/verified/revision_requested)
    public ?string $overdue = null;     // null / 'overdue' / 'on_time'

    protected $queryString = [
        'search' => ['except' => ''],
        'status' => ['except' => 'pending'],
        'overdue' => ['except' => null],
        'page' => ['except' => 1],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function updatingOverdue(): void
    {
        $this->resetPage();
    }

    public function with(): array
    {
        $facultyId = Auth::id();

        $query = LogbookEntry::query()
            ->whereHas('user.internships', function ($q) use ($facultyId) {
                $q->where('faculty_supervisor_id', $facultyId);
            })
            ->with('user')
            ->latest('submitted_at');

        // Filter by supervisor_status (default: pending)
        if ($this->status === 'pending') {
            $query->where('supervisor_status', 'pending');
        } elseif ($this->status) {
            $query->where('supervisor_status', $this->status);
        }

        // Simple search by student name
        if ($this->search !== '') {
            $query->whereHas('user', function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%');
            });
        }

        // Overdue: example heuristic (submitted more than 7 days ago)
        if ($this->overdue === 'overdue') {
            $query->where('submitted_at', '<', now()->subDays(7));
        } elseif ($this->overdue === 'on_time') {
            $query->where('submitted_at', '>=', now()->subDays(7));
        }

        return [
            'entries' => $query->paginate(10),
        ];
    }
}; ?>

<div class="space-y-6">
        {{-- Header --}}
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-2xl font-bold leading-7 text-gray-900 dark:text-gray-100 sm:text-3xl sm:tracking-tight">
                    Logbook Verification
                </h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Review weekly logbooks and PDF verification.
                </p>
            </div>

            {{-- Simple filters (you can style these like the prototype) --}}
            <div class="flex flex-wrap gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Search student</label>
                    <input
                        type="text"
                        wire:model.debounce.500ms="search"
                        class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-slate-800 dark:text-gray-100 text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:focus:border-indigo-400 dark:focus:ring-indigo-400"
                        placeholder="Name or keyword..."
                    />
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Status</label>
                    <select
                        wire:model="status"
                        class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-slate-800 dark:text-gray-100 text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:focus:border-indigo-400 dark:focus:ring-indigo-400"
                    >
                        <option value="pending">Pending</option>
                        <option value="verified">Verified</option>
                        <option value="revision_requested">Revision requested</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Due</label>
                    <select
                        wire:model="overdue"
                        class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-slate-800 dark:text-gray-100 text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:focus:border-indigo-400 dark:focus:ring-indigo-400"
                    >
                        <option value="">All</option>
                        <option value="overdue">Overdue</option>
                        <option value="on_time">On time</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- Queue list --}}
        <div class="rounded-2xl bg-white dark:bg-slate-900/80 shadow-sm ring-1 ring-gray-200 dark:ring-white/10 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Pending logbooks</h3>
                <span class="text-sm text-gray-500 dark:text-gray-400">
                    {{ $entries->total() }} entries
                </span>
            </div>

            @if($entries->isEmpty())
                <p class="text-sm text-gray-500 dark:text-gray-400">No logbooks found for your filters.</p>
            @else
                <div class="space-y-4">
                    @foreach($entries as $entry)
                        @php
                            $status = $entry->supervisor_status ?? 'pending';
                            $statusMap = [
                                'pending' => ['label' => 'Pending', 'class' => 'bg-amber-100 text-amber-800'],
                                'verified' => ['label' => 'Verified', 'class' => 'bg-green-100 text-green-800'],
                                'revision_requested' => ['label' => 'Revision requested', 'class' => 'bg-rose-100 text-rose-800'],
                            ][$status] ?? ['label' => ucfirst($status), 'class' => 'bg-gray-100 text-gray-700'];


                        @endphp

                        <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-4 flex flex-col gap-3">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                        {{ $entry->user->name ?? 'Student' }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        Week {{ $entry->week_number }}
                                        @if($entry->submitted_at)
                                            • Submitted {{ $entry->submitted_at->diffForHumans() }}
                                        @endif
                                    </p>

                                </div>

                                <div class="flex flex-col items-end gap-2">
                                    <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $statusMap['class'] }}">
                                        {{ $statusMap['label'] }}
                                    </span>

                                    <a
                                        href="{{ route('faculty.logbooks.show', $entry) }}"
                                        wire:navigate
                                        class="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700"
                                    >
                                        <i data-lucide="eye" class="h-4 w-4 mr-1"></i>
                                        Review
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-4">
                    {{ $entries->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
