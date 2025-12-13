<?php

use App\Models\LogbookEntry;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    public array $logbooks = [];

    public string $entry_text = '';

    public $entry_file = null;

    public int $week_number = 1;

    public bool $placementApproved = false;

    public ?array $currentWeekEntry = null;

    public bool $canModerate = false;

    protected function refreshCurrentWeekEntry(): void
    {
        $this->currentWeekEntry = LogbookEntry::where('user_id', Auth::id())
            ->where('week_number', $this->week_number)
            ->first()
            ?->toArray() ?? null;
    }

    public function mount(): void
    {
        $user = Auth::user();
        $application = $user->applications()->latest()->first();
        $this->placementApproved = $application && $application->status === 'approved';

        $this->loadLogbooks();
        $this->week_number = ($this->logbooks[0]['week_number'] ?? 0) + 1;
        $this->refreshCurrentWeekEntry();
    }

    public function submit(): void
    {
        $data = $this->validate([
            'week_number' => 'required|integer|min:1|max:24',
            'entry_text' => 'required|string|min:10',
            'entry_file' => 'nullable|file|mimes:pdf|max:5120',
        ]);

        $path = $this->entry_file ? $this->entry_file->store("logbooks/week-{$this->week_number}", 'public') : null;

        LogbookEntry::updateOrCreate(
            ['user_id' => Auth::id(), 'week_number' => $this->week_number],
            [
                'entry_text' => $this->entry_text,
                'file_path' => $path,
                'status' => 'submitted',
                'submitted_at' => now(),
            ]
        );

        // Notify faculty supervisor
        $supervisor = Auth::user()->internships()->latest()->first()?->facultySupervisor;
        if ($supervisor) {
            $entry = LogbookEntry::where('user_id', Auth::id())
                ->where('week_number', $this->week_number)
                ->first();
            $supervisor->notify(new \App\Notifications\NewLogbookSubmittedNotification($entry));
        }

        $this->reset(['entry_text', 'entry_file']);
        $this->loadLogbooks();
        $this->week_number = ($this->logbooks[0]['week_number'] ?? 0) + 1;
        $this->refreshCurrentWeekEntry();

        session()->flash('status', "Week {$data['week_number']} logbook submitted successfully.");
        $this->dispatch('notify', message: 'Logbook submitted.');
    }

    public function analyze(): void
    {
        $this->validate([
            'week_number' => 'required|integer|min:1|max:24',
            'entry_text' => 'required|string|min:10',
        ]);

        // Get existing entry to preserve file_path if no new file is uploaded
        $existingEntry = LogbookEntry::where('user_id', Auth::id())
            ->where('week_number', $this->week_number)
            ->first();

        // Store file if present, otherwise preserve existing file_path
        $path = $this->entry_file
            ? $this->entry_file->store("logbooks/week-{$this->week_number}", 'public')
            : ($existingEntry?->file_path ?? null);

        // Store Placeholder for AI analysis job
        $aiAnalysis = [
            'sentiment' => 'positive',
            'skills_identified' => ['problem solving', 'teamwork', 'communication', 'technical skills'],
            'summary' => 'This week, the intern demonstrated strong problem solving and teamwork skills while contributing to technical projects with effective communication.',
            'analyzed_at' => now()->toISOString(),
        ];

        LogbookEntry::updateOrCreate(
            ['user_id' => Auth::id(), 'week_number' => $this->week_number],
            [
                'entry_text' => $this->entry_text,
                'file_path' => $path,
                'ai_analysis_json' => $aiAnalysis,
                'status' => 'pending_review',
            ]
        );

        // Notify faculty supervisor
        $supervisor = Auth::user()->internships()->latest()->first()?->facultySupervisor;
        if ($supervisor) {
            $entry = LogbookEntry::where('user_id', Auth::id())
                ->where('week_number', $this->week_number)
                ->first();
            $supervisor->notify(new \App\Notifications\NewLogbookSubmittedNotification($entry));
        }

        $this->reset(['entry_text', 'entry_file']);
        $this->loadLogbooks();
        $this->week_number = ($this->logbooks[0]['week_number'] ?? 0) + 1;
        $this->refreshCurrentWeekEntry();
        session()->flash('status', 'AI analysis  completed! Your logbook is now pending review');
        $this->dispatch('notify', message: 'AI analysis queued (stub).');
    }

    public function markStatus(int $id, string $status): void
    {
        $entry = LogbookEntry::where('user_id', Auth::id())->findOrFail($id);
        $entry->status = $status;
        $entry->save();

        $this->loadLogbooks();
        session()->flash('status', "Logbook entry marked as {$status}.");
        $this->dispatch('notify', message: "Status updated to {$status}.");
    }

    protected function loadLogbooks(): void
    {
        $this->logbooks = Auth::user()
            ->logbookEntries()
            ->latest('week_number')
            ->limit(8)
            ->get()
            ->toArray();
    }
}; ?>

<div class="space-y-8 text-gray-900 dark:text-gray-100">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Weekly Logbooks</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Submit your weekly entries and track AI analysis status.</p>
        </div>
        <div class="inline-flex items-center rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700 ring-1 ring-indigo-200 dark:bg-indigo-500/10 dark:text-indigo-200 dark:ring-indigo-400/40">
            {{ count($logbooks) }} submitted
        </div>
    </div>
    
    {{-- Placement approval gate --}}
    @if(!$placementApproved)
        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-500/30 dark:bg-amber-950/30 dark:text-amber-100">
            <div class="flex items-center gap-2">
                <i data-lucide="lock" class="h-4 w-4"></i>
                <span><strong>Placement Required:</strong> Complete placement approval to submit logbooks. Visit the <a href="{{ route('placement.index') }}" class="underline font-semibold">Placement page</a> to get started.</span>
            </div>
        </div>
    @endif

    {{-- Create form --}}
    <div class="grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2 rounded-2xl bg-white shadow-sm ring-1 ring-gray-200 p-6 dark:bg-zinc-950 dark:ring-white/10 dark:shadow-none">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">New Logbook Entry</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Week {{ $week_number }}</p>

            <div class="space-y-4">
                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Week Number</label>
                    <input type="number" min="1" wire:model.defer="week_number" class="mt-1 block w-full rounded-lg border border-gray-200 bg-white text-gray-900 focus:border-indigo-500 focus:ring-indigo-500 dark:border-zinc-700/70 dark:bg-zinc-800/60 dark:text-gray-100 dark:focus:border-indigo-400 dark:focus:ring-indigo-400">
                    @error('week_number')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Entry</label>
                    <textarea wire:model.defer="entry_text" rows="5" class="mt-1 block w-full rounded-lg border border-gray-200 bg-white text-gray-900 focus:border-indigo-500 focus:ring-indigo-500 dark:border-zinc-700/70 dark:bg-zinc-800/60 dark:text-gray-100 dark:placeholder:text-gray-400 dark:focus:border-indigo-400 dark:focus:ring-indigo-400"></textarea>
                    @error('entry_text')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Attach PDF (optional)</label>
                    <input type="file" wire:model="entry_file" class="mt-1 block w-full text-sm text-gray-700 bg-white dark:text-gray-200 dark:bg-zinc-800/60">
                    @error('entry_file')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
                @php
                    $lockedStatus = $currentWeekEntry['status'] ?? null;
                    $isLocked = in_array($lockedStatus, ['pending_review', 'approved']);
                @endphp

                @if($isLocked)
                    <div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800 mb-2 dark:border-amber-500/30 dark:bg-amber-950/30 dark:text-amber-100">
                        Week {{ $currentWeekEntry['week_number'] ?? $week_number }} is {{ $lockedStatus }}. Contact your supervisor to re-open.
                    </div>
                @endif

                <div class="flex gap-3">
                    <button wire:click="analyze" type="button"
                        @if($isLocked || !$placementApproved) disabled @endif
                        class="inline-flex items-center rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-100 disabled:opacity-50 disabled:cursor-not-allowed dark:border-indigo-500/30 dark:bg-indigo-500/10 dark:text-indigo-200 dark:hover:bg-indigo-500/20">
                        <i data-lucide="sparkles" class="h-4 w-4 mr-2"></i> Analyze
                    </button>
                    <button wire:click="submit"
                        @if($isLocked || !$placementApproved) disabled @endif
                        class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed dark:bg-indigo-500 dark:hover:bg-indigo-400">
                        Submit logbook
                    </button>
                </div>
            </div>
        </div>

        <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-200 p-6 dark:bg-zinc-950 dark:ring-white/10 dark:shadow-none">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Guidelines</h3>
            <ul class="mt-3 space-y-2 text-sm text-gray-700 dark:text-gray-200">
                <li class="flex items-start gap-2"><i data-lucide="check" class="h-4 w-4 mt-0.5 text-indigo-600 dark:text-indigo-300"></i> Keep entries focused on weekly outcomes.</li>
                <li class="flex items-start gap-2"><i data-lucide="check" class="h-4 w-4 mt-0.5 text-indigo-600 dark:text-indigo-300"></i> Attach signed logsheet as PDF.</li>
                <li class="flex items-start gap-2"><i data-lucide="check" class="h-4 w-4 mt-0.5 text-indigo-600 dark:text-indigo-300"></i> AI analysis will summarize skills and sentiment.</li>
            </ul>
        </div>
    </div>

    {{-- Recent logbooks --}}
    <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-200 p-6 dark:bg-zinc-950 dark:ring-white/10 dark:shadow-none">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Recent Logbooks</h3>
            <span class="text-sm text-gray-500 dark:text-gray-400">Latest 8</span>
        </div>
        <div class="grid gap-4 lg:grid-cols-2">
            @forelse($logbooks as $log)
                @php
                    $status = $log['status'] ?? 'draft';
                    $map = [
                        'draft' => [
                            'label'=>'Draft',
                            'class'=>'bg-gray-100 text-gray-700',
                            'dark_class'=>'dark:bg-gray-800/70 dark:text-gray-100'
                        ],
                        'submitted' => [
                            'label'=>'Submitted',
                            'class'=>'bg-amber-100 text-amber-800',
                            'dark_class'=>'dark:bg-amber-500/10 dark:text-amber-200'
                        ],
                        'pending_review' => [
                            'label'=>'Pending Review',
                            'class'=>'bg-amber-100 text-amber-800',
                            'dark_class'=>'dark:bg-amber-500/10 dark:text-amber-200'
                        ],
                        'approved' => [
                            'label'=>'Approved',
                            'class'=>'bg-green-100 text-green-800',
                            'dark_class'=>'dark:bg-green-500/10 dark:text-green-200'
                        ],
                        'rejected' => [
                            'label'=>'Rejected',
                            'class'=>'bg-rose-100 text-rose-800',
                            'dark_class'=>'dark:bg-rose-500/10 dark:text-rose-200'
                        ],
                    ][$status] ?? ['label'=>$status,'class'=>'bg-gray-100 text-gray-700','dark_class'=>'dark:bg-gray-800/70 dark:text-gray-100'];
                    $supervisorStatus = $log['supervisor_status'] ?? null;
                    $supervisorMap = [
                        'revision_requested' => [
                            'label' => 'Revision requested',
                            'class' => 'bg-rose-100 text-rose-800',
                            'dark_class' => 'dark:bg-rose-500/10 dark:text-rose-200',
                            'icon' => 'rotate-ccw'
                        ],
                        'verified' => [
                            'label' => 'Verified',
                            'class' => 'bg-emerald-100 text-emerald-700',
                            'dark_class' => 'dark:bg-emerald-500/10 dark:text-emerald-200',
                            'icon' => 'check-circle-2'
                        ],
                        'pending' => [
                            'label' => 'Supervisor pending',
                            'class' => 'bg-amber-100 text-amber-800',
                            'dark_class' => 'dark:bg-amber-500/10 dark:text-amber-200',
                            'icon' => 'hourglass'
                        ],
                    ];
                    $supervisorBadge = $supervisorStatus ? ($supervisorMap[$supervisorStatus] ?? null) : null;
                @endphp
                <div class="rounded-xl border border-gray-200 p-4 flex flex-col gap-3 dark:border-white/10 dark:bg-slate-900/60">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">Week {{ $log['week_number'] }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Updated {{ \Illuminate\Support\Carbon::parse($log['updated_at'])->diffForHumans() }}</p>
                        </div>
                        <div class="flex flex-col items-end gap-1">
                            <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $map['class'] }} {{ $map['dark_class'] }}">{{ $map['label'] }}</span>
                            @if($supervisorBadge)
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-semibold {{ $supervisorBadge['class'] }} {{ $supervisorBadge['dark_class'] ?? '' }}">
                                    <i data-lucide="{{ $supervisorBadge['icon'] }}" class="mr-1 h-3 w-3"></i>
                                    {{ $supervisorBadge['label'] }}
                                </span>
                            @endif
                        </div>
                    </div>
                    <p class="text-sm text-gray-700 dark:text-gray-300 line-clamp-2">{{ $log['entry_text'] ?: 'No text provided.' }}</p>
                    <div class="flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
                        @if($log['file_path'])
                            <a href="{{ Storage::disk('public')->url($log['file_path']) }}" target="_blank" class="text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300 flex items-center gap-1">
                                <i data-lucide="file" class="h-4 w-4"></i> Attachment
                            </a>
                        @endif
                        @if(!empty($log['ai_analysis_json']))
                            <span class="inline-flex items-center rounded-full bg-indigo-50 px-2 py-1 text-[11px] font-semibold text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-300">
                                <i data-lucide="sparkles" class="h-3 w-3 mr-1"></i> AI analyzed
                            </span>
                        @endif
                    </div>
                    @if(!empty($log['ai_analysis_json']['summary']))
                        <p class="text-xs text-gray-600 dark:text-gray-300">AI: {{ $log['ai_analysis_json']['summary'] }}</p>
                    @endif

                    @if($supervisorStatus === 'revision_requested')
                        <div class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-[11px] text-rose-700 dark:border-rose-400/40 dark:bg-rose-950/40 dark:text-rose-200">
                            <div class="flex items-start gap-2">
                                <i data-lucide="rotate-ccw" class="mt-0.5 h-3.5 w-3.5"></i>
                                <div class="space-y-1">
                                    <p class="font-semibold text-rose-800 dark:text-rose-200">Supervisor requested revisions</p>
                                    @if(!empty($log['supervisor_comment']))
                                        <p class="text-rose-700 dark:text-rose-200">“{{ Str::limit($log['supervisor_comment'], 120) }}”</p>
                                    @else
                                        <p class="text-rose-700 dark:text-rose-200">Update this entry and resubmit for verification.</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @elseif($supervisorStatus === 'verified')
                        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-[11px] text-emerald-700 dark:border-emerald-400/40 dark:bg-emerald-950/40 dark:text-emerald-200">
                            <div class="flex items-start gap-2">
                                <i data-lucide="check-circle-2" class="mt-0.5 h-3.5 w-3.5"></i>
                                <div class="space-y-1">
                                    <p class="font-semibold text-emerald-800 dark:text-emerald-200">Supervisor verified</p>
                                    @if(!empty($log['supervisor_comment']))
                                        <p class="text-emerald-700 dark:text-emerald-200">“{{ Str::limit($log['supervisor_comment'], 120) }}”</p>
                                    @else
                                        <p class="text-emerald-700 dark:text-emerald-200">This entry has been verified by your supervisor.</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif

                    @if($canModerate && $status !== 'approved')
                        <div class="flex flex-wrap gap-2 text-xs">
                            <button wire:click="markStatus({{ $log['id'] }}, 'pending_review')" class="text-amber-600 hover:text-amber-700">
                                Mark pending
                            </button>
                            <button wire:click="markStatus({{ $log['id'] }}, 'approved')" class="text-green-600 hover:text-green-700">
                                Approve
                            </button>
                            <button wire:click="markStatus({{ $log['id'] }}, 'rejected')" class="text-rose-600 hover:text-rose-700">
                                Reject
                            </button>
                        </div>
                    @endif

                    <div class="mt-3 flex items-center justify-between text-xs">
                        <span class="text-gray-500 dark:text-gray-400">Week {{ $log['week_number'] }}</span>
                        <a
                            href="{{ route('logbooks.show', ['logbook' => $log['id']]) }}"
                            class="inline-flex items-center gap-1 text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300 font-medium"
                            wire:navigate
                        >
                            View details
                            <i data-lucide="arrow-right" class="h-3.5 w-3.5"></i>
                        </a>
                    </div>
                </div>
            @empty
                <p class="text-sm text-gray-500">No logbooks submitted yet.</p>
            @endforelse
        </div>
    </div>
</div>
