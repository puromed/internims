<?php

use App\Models\LogbookEntry;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new class extends Component {
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

        $this->reset(['entry_text', 'entry_file']);
        $this->loadLogbooks();
        $this->week_number = ($this->logbooks[0]['week_number'] ?? 0) + 1;
        $this->refreshCurrentWeekEntry();

        session()->flash('status',  "Week {$data['week_number']} logbook submitted successfully.");
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

<div class="space-y-8">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-3xl font-bold text-gray-900">Weekly Logbooks</h2>
            <p class="mt-1 text-sm text-gray-500">Submit your weekly entries and track AI analysis status.</p>
        </div>
        <div class="inline-flex items-center rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700 ring-1 ring-indigo-200">
            {{ count($logbooks) }} submitted
        </div>
    </div>
    
    {{-- Placement approval gate --}}
    @if(!$placementApproved)
        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            <div class="flex items-center gap-2">
                <i data-lucide="lock" class="h-4 w-4"></i>
                <span><strong>Placement Required:</strong> Complete placement approval to submit logbooks. Visit the <a href="{{ route('placement.index') }}" class="underline font-semibold">Placement page</a> to get started.</span>
            </div>
        </div>
    @endif

    {{-- Create form --}}
    <div class="grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2 rounded-2xl bg-white shadow-sm ring-1 ring-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900">New Logbook Entry</h3>
            <p class="text-sm text-gray-500 mb-4">Week {{ $week_number }}</p>

            <div class="space-y-4">
                <div>
                    <label class="text-sm font-medium text-gray-700">Week Number</label>
                    <input type="number" min="1" wire:model.defer="week_number" class="mt-1 block w-full rounded-lg border border-gray-200 bg-white text-gray-900 focus:border-indigo-500 focus:ring-indigo-500">
                    @error('week_number')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700">Entry</label>
                    <textarea wire:model.defer="entry_text" rows="5" class="mt-1 block w-full rounded-lg border border-gray-200 bg-white text-gray-900 focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                    @error('entry_text')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700">Attach PDF (optional)</label>
                    <input type="file" wire:model="entry_file" class="mt-1 block w-full text-sm text-gray-700 bg-white">
                    @error('entry_file')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
                @php
                    $lockedStatus = $currentWeekEntry['status'] ?? null;
                    $isLocked = in_array($lockedStatus, ['pending_review', 'approved']);
                @endphp

                @if($isLocked)
                    <div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800 mb-2">
                        Week {{ $currentWeekEntry['week_number'] ?? $week_number }} is {{ $lockedStatus }}. Contact your supervisor to re-open.
                    </div>
                @endif

                <div class="flex gap-3">
                    <button wire:click="analyze" type="button"
                        @if($isLocked || !$placementApproved) disabled @endif
                        class="inline-flex items-center rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-100 disabled:opacity-50 disabled:cursor-not-allowed">
                        <i data-lucide="sparkles" class="h-4 w-4 mr-2"></i> Analyze
                    </button>
                    <button wire:click="submit"
                        @if($isLocked || !$placementApproved) disabled @endif
                        class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed">
                        Submit logbook
                    </button>
                </div>
            </div>
        </div>

        <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900">Guidelines</h3>
            <ul class="mt-3 space-y-2 text-sm text-gray-700">
                <li class="flex items-start gap-2"><i data-lucide="check" class="h-4 w-4 mt-0.5 text-indigo-600"></i> Keep entries focused on weekly outcomes.</li>
                <li class="flex items-start gap-2"><i data-lucide="check" class="h-4 w-4 mt-0.5 text-indigo-600"></i> Attach signed logsheet as PDF.</li>
                <li class="flex items-start gap-2"><i data-lucide="check" class="h-4 w-4 mt-0.5 text-indigo-600"></i> AI analysis will summarize skills and sentiment.</li>
            </ul>
        </div>
    </div>

    {{-- Recent logbooks --}}
    <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Recent Logbooks</h3>
            <span class="text-sm text-gray-500">Latest 8</span>
        </div>
        <div class="grid gap-4 lg:grid-cols-2">
            @forelse($logbooks as $log)
                @php
                    $status = $log['status'] ?? 'draft';
                    $map = [
                        'draft' => ['label'=>'Draft','class'=>'bg-gray-100 text-gray-700'],
                        'submitted' => ['label'=>'Submitted','class'=>'bg-amber-100 text-amber-800'],
                        'pending_review' => ['label'=>'Pending Review','class'=>'bg-amber-100 text-amber-800'],
                        'approved' => ['label'=>'Approved','class'=>'bg-green-100 text-green-800'],
                        'rejected' => ['label'=>'Rejected','class'=>'bg-rose-100 text-rose-800'],
                    ][$status] ?? ['label'=>$status,'class'=>'bg-gray-100 text-gray-700'];
                @endphp
                <div class="rounded-xl border border-gray-200 p-4 flex flex-col gap-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-semibold text-gray-900">Week {{ $log['week_number'] }}</p>
                            <p class="text-xs text-gray-500">Updated {{ \Illuminate\Support\Carbon::parse($log['updated_at'])->diffForHumans() }}</p>
                        </div>
                        <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $map['class'] }}">{{ $map['label'] }}</span>
                    </div>
                    <p class="text-sm text-gray-700 line-clamp-2">{{ $log['entry_text'] ?: 'No text provided.' }}</p>
                    <div class="flex items-center gap-3 text-xs text-gray-500">
                        @if($log['file_path'])
                            <a href="{{ Storage::disk('public')->url($log['file_path']) }}" target="_blank" class="text-indigo-600 hover:text-indigo-700 flex items-center gap-1">
                                <i data-lucide="file" class="h-4 w-4"></i> Attachment
                            </a>
                        @endif
                        @if(!empty($log['ai_analysis_json']))
                            <span class="inline-flex items-center rounded-full bg-indigo-50 px-2 py-1 text-[11px] font-semibold text-indigo-700">
                                <i data-lucide="sparkles" class="h-3 w-3 mr-1"></i> AI analyzed
                            </span>
                        @endif
                    </div>
                    @if(!empty($log['ai_analysis_json']['summary']))
                        <p class="text-xs text-gray-600">AI: {{ $log['ai_analysis_json']['summary'] }}</p>
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
                        <span class="text-gray-500">Week {{ $log['week_number'] }}</span>
                        <a 
                            href="{{ route('logbooks.show', ['logbook' => $log['id']]) }}" 
                            class="inline-flex items-center gap-1 text-indigo-600 hover:text-indigo-700 font-medium"
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
