<?php

use App\Models\LogbookEntry;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Component;

new class extends Component {
    use AuthorizesRequests;

    public LogbookEntry $logbook;
    public string $comment = '';

    public function mount(LogbookEntry $logbook): void
    {
        $this->authorize('view', $logbook);

        $this->logbook = $logbook->loadMissing(['user', 'reviewer']);
        $this->comment = (string) ($this->logbook->supervisor_comment ?? '');
    }

    public function approve(): void
    {
        $this->authorize('review', $this->logbook);

        $this->validate([
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->logbook->forceFill([
            'supervisor_status' => 'verified',
            'supervisor_comment' => $this->comment ?: null,
            'reviewed_at' => now(),
            'reviewed_by' => auth()->id(),
            'status' => 'approved',
        ])->save();

        session()->flash('status', 'Logbook approved.');
        $this->dispatch('notify', message: 'Logbook approved.');
    }

    public function requestRevision(): void
    {
        $this->authorize('review', $this->logbook);

        $this->validate([
            'comment' => ['required', 'string', 'max:2000'],
        ]);

        $this->logbook->forceFill([
            'supervisor_status' => 'revision_requested',
            'supervisor_comment' => $this->comment,
            'reviewed_at' => now(),
            'reviewed_by' => auth()->id(),
            'status' => 'submitted', // re-open for student
        ])->save();

        session()->flash('status', 'Revision requested.');
        $this->dispatch('notify', message: 'Revision requested from student.');
    }
}; ?>

<div class="space-y-6">
        {{-- Header --}}
        <div class="md:flex md:items-center md:justify-between">
            <div class="min-w-0 flex-1">
                <div class="flex items-center gap-3 mb-2">
                    <a href="{{ route('faculty.logbooks.index') }}" class="inline-flex items-center gap-1 text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300" wire:navigate>
                        <i data-lucide="arrow-left" class="h-4 w-4"></i>
                        Back to Logbooks
                    </a>
                </div>
                <h2 class="text-2xl font-bold leading-7 text-gray-900 dark:text-gray-100 sm:text-3xl sm:tracking-tight">
                    Week {{ $logbook->week_number }} – {{ $logbook->user->name ?? 'Student' }}
                </h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    AI insights on the left, PDF logsheet on the right.
                </p>
            </div>

            {{-- Status + supervisor badge --}}
            <div class="mt-4 flex flex-col items-end gap-2 md:ml-4 md:mt-0 text-right">
                @php
                    $statusConfig = [
                        'draft' => [
                            'label' => 'Draft',
                            'class' => 'bg-gray-100 text-gray-800',
                            'dark_class' => 'dark:bg-gray-800/70 dark:text-gray-100',
                            'icon' => 'file-text',
                        ],
                        'submitted' => [
                            'label' => 'Submitted',
                            'class' => 'bg-amber-100 text-amber-800',
                            'dark_class' => 'dark:bg-amber-500/10 dark:text-amber-200',
                            'icon' => 'check',
                        ],
                        'pending_review' => [
                            'label' => 'Pending Review',
                            'class' => 'bg-amber-100 text-amber-800',
                            'dark_class' => 'dark:bg-amber-500/10 dark:text-amber-200',
                            'icon' => 'clock',
                        ],
                        'approved' => [
                            'label' => 'Approved',
                            'class' => 'bg-green-100 text-green-800',
                            'dark_class' => 'dark:bg-green-500/10 dark:text-green-200',
                            'icon' => 'check-circle',
                        ],
                        'rejected' => [
                            'label' => 'Rejected',
                            'class' => 'bg-rose-100 text-rose-800',
                            'dark_class' => 'dark:bg-rose-500/10 dark:text-rose-200',
                            'icon' => 'x-circle',
                        ],
                    ];

                    $status = $logbook->status ?? 'draft';
                    $config = $statusConfig[$status] ?? $statusConfig['draft'];

                    $supervisorStatusConfig = [
                        'revision_requested' => [
                            'label' => 'Revision requested',
                            'class' => 'bg-rose-100 text-rose-800',
                            'dark_class' => 'dark:bg-rose-500/10 dark:text-rose-200',
                            'icon' => 'rotate-ccw',
                        ],
                        'verified' => [
                            'label' => 'Verified',
                            'class' => 'bg-green-100 text-green-800',
                            'dark_class' => 'dark:bg-green-500/10 dark:text-green-200',
                            'icon' => 'check-circle-2',
                        ],
                        'pending' => [
                            'label' => 'Supervisor pending',
                            'class' => 'bg-amber-100 text-amber-800',
                            'dark_class' => 'dark:bg-amber-500/10 dark:text-amber-200',
                            'icon' => 'hourglass',
                        ],
                    ];

                    $supervisorStatus = $logbook->supervisor_status ?? null;
                    $supervisorBadge = $supervisorStatus
                        ? ($supervisorStatusConfig[$supervisorStatus] ?? null)
                        : null;
                @endphp

                <span class="inline-flex items-center rounded-full px-3 py-1.5 text-sm font-medium {{ $config['class'] }} {{ $config['dark_class'] ?? '' }} ring-1 ring-inset dark:ring-white/20">
                    <i data-lucide="{{ $config['icon'] }}" class="mr-1.5 h-4 w-4"></i>
                    {{ $config['label'] }}
                </span>

                @if($supervisorBadge)
                    <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $supervisorBadge['class'] }} {{ $supervisorBadge['dark_class'] ?? '' }} ring-1 ring-inset dark:ring-white/20">
                        <i data-lucide="{{ $supervisorBadge['icon'] }}" class="mr-1 h-3.5 w-3.5"></i>
                        {{ $supervisorBadge['label'] }}
                    </span>
                @endif

                <span class="text-xs text-gray-500">
                    @if($logbook->submitted_at)
                        Submitted {{ $logbook->submitted_at->format('M d, Y H:i') }}
                    @else
                        Not yet submitted
                    @endif
                </span>
            </div>
        </div>

        {{-- Two-column layout --}}
        <div class="grid gap-6 lg:grid-cols-3">
            {{-- Left: AI insights + comment box + action buttons --}}
            <div class="lg:col-span-1 space-y-6">
                {{-- AI insights card: you can largely reuse the AI card from student logbook show --}}
                {{-- Supervisor decision block --}}
                <div class="overflow-hidden rounded-2xl bg-white dark:bg-slate-900/80 shadow-sm ring-1 ring-gray-200 dark:ring-white/10">
                    <div class="px-6 py-5 border-b border-gray-100 dark:border-gray-700">
                        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Supervisor Decision</h3>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Approve or request revision. Comments are required for revisions.
                        </p>
                    </div>
                    <div class="p-6 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Comments to student
                            </label>
                            <textarea
                                wire:model.defer="comment"
                                rows="4"
                                class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-slate-800 dark:text-gray-100 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:focus:border-indigo-400 dark:focus:ring-indigo-400"
                                placeholder="Explain your decision or requested changes..."
                            ></textarea>
                            @error('comment')
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex flex-col sm:flex-row sm:justify-end gap-3">
                            <button
                                type="button"
                                wire:click="requestRevision"
                                wire:target="requestRevision,approve"
                                wire:loading.attr="disabled"
                                wire:loading.class="opacity-75 cursor-not-allowed"
                                class="inline-flex items-center justify-center rounded-lg border border-rose-200 bg-rose-50 px-4 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-100"
                            >
                                <i data-lucide="rotate-ccw" class="h-4 w-4 mr-2"></i>
                                Request revision
                            </button>
                            <button
                                type="button"
                                wire:click="approve"
                                wire:target="requestRevision,approve"
                                wire:loading.attr="disabled"
                                wire:loading.class="opacity-75 cursor-not-allowed"
                                class="inline-flex items-center justify-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700"
                            >
                                <i data-lucide="check-circle-2" class="h-4 w-4 mr-2"></i>
                                Approve logbook
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right: PDF viewer + entry text (if you want) --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Optional: show entry_text similar to student view --}}
                {{-- PDF viewer card --}}
                <div class="overflow-hidden rounded-2xl bg-white dark:bg-slate-900/80 shadow-sm ring-1 ring-gray-200 dark:ring-white/10">
                    <div class="px-6 py-5 border-b border-gray-100 dark:border-gray-700 flex items-center gap-3">
                        <div class="h-10 w-10 rounded-lg bg-indigo-50 dark:bg-indigo-500/10 flex items-center justify-center">
                            <i data-lucide="file-text" class="h-5 w-5 text-indigo-600 dark:text-indigo-400"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900 dark:text-gray-100">Signed Logsheet</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Embedded PDF viewer with download fallback.
                            </p>
                        </div>
                    </div>

                    <div class="p-6 space-y-4">
                        @if($logbook->file_path)
                            <div class="aspect-[3/4] w-full rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden bg-gray-50 dark:bg-slate-800">
                                <iframe
                                    src="{{ Storage::disk('public')->url($logbook->file_path) }}"
                                    class="h-full w-full"
                                ></iframe>
                            </div>

                            <a
                                href="{{ Storage::disk('public')->url($logbook->file_path) }}"
                                target="_blank"
                                rel="noopener"
                                class="inline-flex items-center gap-2 text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300"
                            >
                                <i data-lucide="download" class="h-4 w-4"></i>
                                Open or download PDF in new tab
                            </a>
                        @else
                            <p class="text-sm text-gray-500 dark:text-gray-400">No logsheet has been uploaded for this entry.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
