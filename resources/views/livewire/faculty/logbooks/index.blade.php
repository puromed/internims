<?php

use App\Models\LogbookEntry;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public ?string $status = 'pending';
    public ?string $overdue = null;
    
    // Bulk selection
    public array $selected = [];
    public bool $selectAll = false;
    public bool $showRevisionModal = false;
    public string $bulkComment = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'status' => ['except' => 'pending'],
        'overdue' => ['except' => null],
        'page' => ['except' => 1],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
        $this->selected = [];
        $this->selectAll = false;
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
        $this->selected = [];
        $this->selectAll = false;
    }

    public function updatingOverdue(): void
    {
        $this->resetPage();
        $this->selected = [];
        $this->selectAll = false;
    }

    public function updatedSelectAll(bool $value): void
    {
        if ($value) {
            $this->selected = $this->getVisibleEntryIds();
        } else {
            $this->selected = [];
        }
    }

    public function bulkApprove(): void
    {
        if (empty($this->selected)) {
            return;
        }

        $facultyId = Auth::id();
        
        LogbookEntry::whereIn('id', $this->selected)
            ->whereHas('user.internships', fn($q) => $q->where('faculty_supervisor_id', $facultyId))
            ->update([
                'supervisor_status' => 'verified',
                'reviewed_at' => now(),
                'reviewed_by' => $facultyId,
                'status' => 'approved',
            ]);

        // Send notifications
        $entries = LogbookEntry::with('user')->whereIn('id', $this->selected)->get();
        foreach ($entries as $entry) {
            $entry->user->notify(new \App\Notifications\LogbookEntryApprovedNotification($entry));
        }

        $count = count($this->selected);
        $this->selected = [];
        $this->selectAll = false;
        
        session()->flash('status', "{$count} logbook(s) approved.");
        $this->dispatch('notify', message: "{$count} logbook(s) approved.");
    }

    public function openRevisionModal(): void
    {
        if (empty($this->selected)) {
            return;
        }
        $this->showRevisionModal = true;
        $this->bulkComment = '';
    }

    public function closeRevisionModal(): void
    {
        $this->showRevisionModal = false;
        $this->bulkComment = '';
    }

    public function bulkRequestRevision(): void
    {
        if (empty($this->selected)) {
            return;
        }

        $this->validate([
            'bulkComment' => 'required|string|max:2000',
        ]);

        $facultyId = Auth::id();
        
        LogbookEntry::whereIn('id', $this->selected)
            ->whereHas('user.internships', fn($q) => $q->where('faculty_supervisor_id', $facultyId))
            ->update([
                'supervisor_status' => 'revision_requested',
                'supervisor_comment' => $this->bulkComment,
                'reviewed_at' => now(),
                'reviewed_by' => $facultyId,
                'status' => 'submitted',
            ]);

        // Send notifications
        $entries = LogbookEntry::with('user')->whereIn('id', $this->selected)->get();
        foreach ($entries as $entry) {
            $entry->user->notify(new \App\Notifications\LogbookEntryRevisionRequestedNotification($entry));
        }

        $count = count($this->selected);
        $this->selected = [];
        $this->selectAll = false;
        $this->showRevisionModal = false;
        $this->bulkComment = '';
        
        session()->flash('status', "Revision requested for {$count} logbook(s).");
        $this->dispatch('notify', message: "Revision requested for {$count} logbook(s).");
    }

    protected function getVisibleEntryIds(): array
    {
        $facultyId = Auth::id();
        
        $query = LogbookEntry::query()
            ->whereHas('user.internships', fn($q) => $q->where('faculty_supervisor_id', $facultyId));

        if ($this->status === 'pending') {
            $query->where('supervisor_status', 'pending');
        } elseif ($this->status) {
            $query->where('supervisor_status', $this->status);
        }

        if ($this->search !== '') {
            $query->whereHas('user', fn($q) => $q->where('name', 'like', '%' . $this->search . '%'));
        }

        if ($this->overdue === 'overdue') {
            $query->where('submitted_at', '<', now()->subDays(7));
        } elseif ($this->overdue === 'on_time') {
            $query->where('submitted_at', '>=', now()->subDays(7));
        }

        return $query->pluck('id')->toArray();
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
            {{-- Header with bulk actions --}}
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-4">
                <div class="flex items-center gap-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Pending logbooks</h3>
                    <span class="text-sm text-gray-500 dark:text-gray-400">
                        {{ $entries->total() }} entries
                    </span>
                </div>

                {{-- Bulk action buttons --}}
                @if(count($selected) > 0)
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-indigo-600 dark:text-indigo-400 font-medium">
                            {{ count($selected) }} selected
                        </span>
                        <button
                            wire:click="bulkApprove"
                            wire:loading.attr="disabled"
                            class="inline-flex items-center rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700 disabled:opacity-50"
                        >
                            <i data-lucide="check-circle" class="h-4 w-4 mr-1"></i>
                            Approve Selected
                        </button>
                        <button
                            wire:click="openRevisionModal"
                            wire:loading.attr="disabled"
                            class="inline-flex items-center rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-100 disabled:opacity-50 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-200"
                        >
                            <i data-lucide="rotate-ccw" class="h-4 w-4 mr-1"></i>
                            Request Revision
                        </button>
                    </div>
                @endif
            </div>

            @if($entries->isEmpty())
                <p class="text-sm text-gray-500 dark:text-gray-400">No logbooks found for your filters.</p>
            @else
                {{-- Select All checkbox --}}
                <div class="flex items-center gap-2 mb-4 pb-4 border-b border-gray-100 dark:border-gray-700">
                    <input
                        type="checkbox"
                        wire:model.live="selectAll"
                        id="selectAll"
                        class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-600 dark:bg-slate-800"
                    />
                    <label for="selectAll" class="text-sm text-gray-600 dark:text-gray-400">
                        Select all on this page
                    </label>
                </div>

                <div class="space-y-4">
                    @foreach($entries as $entry)
                        @php
                            $status = $entry->supervisor_status ?? 'pending';
                            $statusMap = [
                                'pending' => ['label' => 'Pending', 'class' => 'bg-amber-100 text-amber-800 dark:bg-amber-500/10 dark:text-amber-200'],
                                'verified' => ['label' => 'Verified', 'class' => 'bg-green-100 text-green-800 dark:bg-green-500/10 dark:text-green-200'],
                                'revision_requested' => ['label' => 'Revision requested', 'class' => 'bg-rose-100 text-rose-800 dark:bg-rose-500/10 dark:text-rose-200'],
                            ][$status] ?? ['label' => ucfirst($status), 'class' => 'bg-gray-100 text-gray-700'];
                        @endphp

                        <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-4 flex items-start gap-4 {{ in_array($entry->id, $selected) ? 'ring-2 ring-indigo-500 dark:ring-indigo-400' : '' }}">
                            {{-- Checkbox --}}
                            <input
                                type="checkbox"
                                wire:model.live="selected"
                                value="{{ $entry->id }}"
                                class="mt-1 h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-600 dark:bg-slate-800"
                            />

                            {{-- Entry content --}}
                            <div class="flex-1 flex items-start justify-between gap-3">
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

        {{-- Bulk Revision Modal --}}
        @if($showRevisionModal)
            <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                    {{-- Backdrop --}}
                    <div wire:click="closeRevisionModal" class="fixed inset-0 bg-gray-500/75 dark:bg-gray-900/80 transition-opacity"></div>

                    {{-- Modal panel --}}
                    <div class="relative inline-block transform overflow-hidden rounded-2xl bg-white dark:bg-slate-900 text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:align-middle">
                        <div class="px-6 py-5 border-b border-gray-100 dark:border-gray-700">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                Request Revision for {{ count($selected) }} Logbook(s)
                            </h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Enter a comment that will be sent to all selected students.
                            </p>
                        </div>

                        <div class="p-6">
                            <textarea
                                wire:model="bulkComment"
                                rows="4"
                                class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-slate-800 dark:text-gray-100 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="Please add more details about your activities this week..."
                            ></textarea>
                            @error('bulkComment')
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="px-6 py-4 bg-gray-50 dark:bg-slate-800/50 flex justify-end gap-3">
                            <button
                                wire:click="closeRevisionModal"
                                class="inline-flex items-center rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-slate-800 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-slate-700"
                            >
                                Cancel
                            </button>
                            <button
                                wire:click="bulkRequestRevision"
                                wire:loading.attr="disabled"
                                class="inline-flex items-center rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700 disabled:opacity-50"
                            >
                                <i data-lucide="send" class="h-4 w-4 mr-2"></i>
                                Send Revision Request
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
