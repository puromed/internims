<?php

use App\Models\EligibilityDoc;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Component;

new class extends Component {
    /**
     * @var array<int, string>
     */
    public array $requiredDocTypes = ['resume', 'transcript', 'offer_letter'];

    public string $search = '';
    public string $statusFilter = 'all';
    
    // PDF Preview State
    public bool $showPdfModal = false;
    public string $pdfUrl = '';
    public string $pdfName = '';

    public function updatedSearch(): void
    {
        // Triggers re-render
    }

    public function updatedStatusFilter(): void
    {
        // Triggers re-render
    }

    public function openPdfPreview(string $path): void
    {
        $this->pdfUrl = Storage::disk('public')->url($path);
        $this->pdfName = basename($path);
        $this->showPdfModal = true;
    }

    public function getOverallEligibilityStatus(User $student): string
    {
        $docs = $student->eligibilityDocs->keyBy('type');

        $hasRejected = $docs->contains(fn (EligibilityDoc $doc) => $doc->status === 'rejected');
        if ($hasRejected) {
            return 'rejected';
        }

        $allApproved = collect($this->requiredDocTypes)->every(
            fn (string $type) => ($docs->get($type)?->status ?? '') === 'approved'
        );

        if ($allApproved) {
            return 'approved';
        }

        return 'pending';
    }

    public function hasAllRequiredDocs(User $student): bool
    {
        $docs = $student->eligibilityDocs->keyBy('type');

        return collect($this->requiredDocTypes)->every(
            fn (string $type) => filled($docs->get($type)?->path)
        );
    }

    public function approve(int $studentId): void
    {
        $student = User::query()
            ->with('eligibilityDocs')
            ->whereKey($studentId)
            ->firstOrFail();

        if (! $this->hasAllRequiredDocs($student)) {
            $this->dispatch('start-toast', message: 'Cannot approve: student is missing required documents.');
            return;
        }

        EligibilityDoc::query()
            ->where('user_id', $student->id)
            ->whereIn('type', $this->requiredDocTypes)
            ->update([
                'status' => 'approved',
                'reviewed_at' => now(),
            ]);

        $student->notify(new \App\Notifications\EligibilityStatusNotification($student, 'approved'));

        $this->dispatch('start-toast', message: 'Eligibility approved successfully.');
    }

    public function reject(int $studentId): void
    {
        $student = User::query()
            ->with('eligibilityDocs')
            ->whereKey($studentId)
            ->firstOrFail();

        EligibilityDoc::query()
            ->where('user_id', $student->id)
            ->whereIn('type', $this->requiredDocTypes)
            ->update([
                'status' => 'rejected',
                'reviewed_at' => now(),
            ]);

        $student->notify(new \App\Notifications\EligibilityStatusNotification($student, 'rejected'));

        $this->dispatch('start-toast', message: 'Eligibility rejected.');
    }

    public function approveAllPending(): void
    {
        $students = User::query()
            ->where('role', 'student')
            ->whereHas('eligibilityDocs')
            ->with('eligibilityDocs')
            ->get()
            ->filter(fn (User $student) => $this->getOverallEligibilityStatus($student) === 'pending')
            ->filter(fn (User $student) => $this->hasAllRequiredDocs($student));

        foreach ($students as $student) {
            EligibilityDoc::query()
                ->where('user_id', $student->id)
                ->whereIn('type', $this->requiredDocTypes)
                ->update([
                    'status' => 'approved',
                    'reviewed_at' => now(),
                ]);

            $student->notify(new \App\Notifications\EligibilityStatusNotification($student, 'approved'));
        }

        $this->dispatch('start-toast', message: $students->count() . ' students approved.');
    }

    public function with(): array
    {
        $countsStudents = User::query()
            ->where('role', 'student')
            ->whereHas('eligibilityDocs')
            ->with(['eligibilityDocs' => fn ($query) => $query->whereIn('type', $this->requiredDocTypes)])
            ->get();

        $counts = [
            'all' => $countsStudents->count(),
            'pending' => $countsStudents->filter(fn (User $student) => $this->getOverallEligibilityStatus($student) === 'pending')->count(),
            'approved' => $countsStudents->filter(fn (User $student) => $this->getOverallEligibilityStatus($student) === 'approved')->count(),
            'rejected' => $countsStudents->filter(fn (User $student) => $this->getOverallEligibilityStatus($student) === 'rejected')->count(),
        ];

        $students = User::query()
            ->where('role', 'student')
            ->whereHas('eligibilityDocs')
            ->with(['eligibilityDocs' => fn ($query) => $query->whereIn('type', $this->requiredDocTypes)])
            ->latest()
            ->get();

        if ($this->search !== '') {
            $students = $students->filter(function (User $student): bool {
                $search = mb_strtolower($this->search);

                return str_contains(mb_strtolower($student->name), $search)
                    || str_contains(mb_strtolower($student->email), $search);
            });
        }

        if ($this->statusFilter !== 'all') {
            $students = $students->filter(
                fn (User $student) => $this->getOverallEligibilityStatus($student) === $this->statusFilter
            );
        }

        return [
            'students' => $students,
            'counts' => $counts,
        ];
    }
}; ?>

<div class="space-y-6">
    {{-- Toast Notification --}}
    <div
        x-data="{ show: false, message: '' }"
        x-on:start-toast.window="show = true; message = $event.detail.message; setTimeout(() => show = false, 3000)"
        x-show="show"
        x-transition:enter="transition transform duration-300"
        x-transition:enter-start="translate-y-full opacity-0"
        x-transition:enter-end="translate-y-0 opacity-100"
        x-transition:leave="transition transform duration-300"
        x-transition:leave-start="translate-y-0 opacity-100"
        x-transition:leave-end="translate-y-full opacity-0"
        class="fixed bottom-4 right-4 z-50 flex items-center gap-3 rounded-xl bg-white px-4 py-3 shadow-lg ring-1 ring-gray-200"
        style="display: none;"
    >
        <i data-lucide="check-circle" class="h-5 w-5 text-emerald-500"></i>
        <p class="text-sm font-semibold text-gray-900" x-text="message"></p>
    </div>

    {{-- PDF Preview Modal --}}
    <flux:modal wire:model="showPdfModal" class="min-w-[50rem]">
        <div class="space-y-4">
            <div class="flex items-center justify-between border-b border-gray-200 pb-4">
                <div>
                    <h3 class="text-sm font-semibold text-gray-900">Document Preview</h3>
                    <p class="text-xs text-gray-500">{{ $pdfName }}</p>
                </div>
            </div>
            
            <div class="bg-gray-100 rounded-lg overflow-hidden h-[600px] flex items-center justify-center">
                @if($pdfUrl)
                    <iframe src="{{ $pdfUrl }}" class="w-full h-full" frameborder="0"></iframe>
                @else
                    <div class="text-center text-gray-500">
                        <i data-lucide="file-off" class="mx-auto h-16 w-16 text-gray-300"></i>
                        <p class="mt-4 text-sm">No preview available</p>
                    </div>
                @endif
            </div>

            <div class="flex justify-end pt-4 gap-2">
                <flux:button wire:click="$set('showPdfModal', false)">Close</flux:button>
                @if($pdfUrl)
                    <flux:button variant="primary" href="{{ $pdfUrl }}" target="_blank">Download</flux:button>
                @endif
            </div>
        </div>
    </flux:modal>

    {{-- Header --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Admin</p>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Eligibility Review</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Approve or reject required documents for Stage 1.</p>
        </div>
        <div class="flex items-center gap-2">
            <flux:button icon="arrow-down-tray">Export</flux:button>
            @if($counts['pending'] > 0)
                <flux:button variant="primary" icon="check" wire:click="approveAllPending" wire:confirm="Approve all {{ $counts['pending'] }} pending applications?">
                    Approve all pending
                </flux:button>
            @endif
        </div>
    </div>

    {{-- Stats Overview --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="flex flex-col gap-1 rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-zinc-900">
            <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Total Applications</span>
            <span class="text-2xl font-bold text-gray-900 dark:text-white">{{ $counts['all'] }}</span>
        </div>
        <div class="flex flex-col gap-1 rounded-xl border border-amber-200 bg-amber-50/50 p-4 shadow-sm dark:border-amber-500/20 dark:bg-amber-500/5">
            <span class="text-xs font-medium text-amber-600 dark:text-amber-400">Pending Review</span>
            <span class="text-2xl font-bold text-amber-700 dark:text-amber-500">{{ $counts['pending'] }}</span>
        </div>
        <div class="flex flex-col gap-1 rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-zinc-900">
            <span class="text-xs font-medium text-emerald-600 dark:text-emerald-400">Approved</span>
            <span class="text-2xl font-bold text-emerald-700 dark:text-emerald-500">{{ $counts['approved'] }}</span>
        </div>
        <div class="flex flex-col gap-1 rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-zinc-900">
            <span class="text-xs font-medium text-rose-600 dark:text-rose-400">Rejected</span>
            <span class="text-2xl font-bold text-rose-700 dark:text-rose-500">{{ $counts['rejected'] }}</span>
        </div>
    </div>

    {{-- Filters --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-wrap gap-2">
            <flux:button size="sm" variant="{{ $statusFilter === 'all' ? 'filled' : 'subtle' }}" wire:click="$set('statusFilter', 'all')" icon="list-bullet">All ({{ $counts['all'] }})</flux:button>
            <flux:button size="sm" variant="{{ $statusFilter === 'pending' ? 'filled' : 'subtle' }}" wire:click="$set('statusFilter', 'pending')" icon="clock" class="{{ $statusFilter === 'pending' ? 'bg-amber-100 text-amber-700 dark:bg-amber-900 dark:text-amber-300' : '' }}">Pending ({{ $counts['pending'] }})</flux:button>
            <flux:button size="sm" variant="{{ $statusFilter === 'approved' ? 'filled' : 'subtle' }}" wire:click="$set('statusFilter', 'approved')" icon="check-circle" class="{{ $statusFilter === 'approved' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900 dark:text-emerald-300' : '' }}">Approved ({{ $counts['approved'] }})</flux:button>
            <flux:button size="sm" variant="{{ $statusFilter === 'rejected' ? 'filled' : 'subtle' }}" wire:click="$set('statusFilter', 'rejected')" icon="x-circle" class="{{ $statusFilter === 'rejected' ? 'bg-rose-100 text-rose-700 dark:bg-rose-900 dark:text-rose-300' : '' }}">Rejected ({{ $counts['rejected'] }})</flux:button>
        </div>
        <div class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-center sm:justify-end sm:gap-3">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Search students" class="w-full sm:w-64" />
            
            <div class="w-full sm:w-40">
                <select class="w-full rounded-xl border-gray-200 px-3 py-2 text-sm text-gray-700 focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-slate-800 dark:text-gray-100">
                    <option>Program</option>
                    <option>Computer Science</option>
                    <option>Information Systems</option>
                    <option>Software Engineering</option>
                </select>
            </div>
        </div>
    </div>

	    {{-- Queue --}}
	    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
	        @forelse($students as $student)
	            @php
	                $docsByType = $student->eligibilityDocs->keyBy('type');
	                $status = $this->getOverallEligibilityStatus($student);
	                $canApprove = $this->hasAllRequiredDocs($student);
	                $initials = collect(explode(' ', $student->name ?? 'U'))->map(fn($w) => strtoupper(substr($w, 0, 1)))->take(2)->join('');
	                
	                $statusConfig = [
	                    'pending' => ['label' => 'Pending', 'color' => 'bg-amber-50 text-amber-600 ring-amber-100', 'icon' => 'clock'],
	                    'approved' => ['label' => 'Approved', 'color' => 'bg-emerald-50 text-emerald-600 ring-emerald-100', 'icon' => 'check-circle'],
	                    'rejected' => ['label' => 'Rejected', 'color' => 'bg-rose-50 text-rose-600 ring-rose-100', 'icon' => 'x-circle'],
	                ];
	                $style = $statusConfig[$status] ?? $statusConfig['pending'];
	                
	                // Documents Status
	                $docs = [
	                    'resume' => ['label' => 'Resume', 'doc' => $docsByType->get('resume')],
	                    'transcript' => ['label' => 'Transcript', 'doc' => $docsByType->get('transcript')],
	                    'offer_letter' => ['label' => 'Offer Letter', 'doc' => $docsByType->get('offer_letter')],
	                ];
	            @endphp

            <div class="space-y-4 rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-zinc-900">
                {{-- Card Header --}}
	                <div class="flex items-start justify-between">
	                    <div class="flex items-center gap-3">
	                        <div class="h-10 w-10 rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center text-sm font-semibold text-zinc-700 dark:text-zinc-300">
	                            {{ $initials }}
	                        </div>
	                        <div>
	                            <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $student->name }}</p>
	                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $student->email }}</p>
	                        </div>
	                    </div>
                    <span class="inline-flex items-center gap-1.5 rounded-full px-2 py-1 text-xs font-medium ring-1 ring-inset {{ $style['color'] }}">
                        <flux:icon name="{{ $style['icon'] }}" class="size-3.5" />
                        {{ $style['label'] }}
                    </span>
                </div>

                {{-- Documents Grid --}}
	                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
	                    @foreach($docs as $type => $doc)
	                        @php
	                            $uploaded = filled($doc['doc']?->path);
	                            $docStatus = $doc['doc']?->status ?? 'missing';
	                            $badge = match ($docStatus) {
	                                'approved' => ['label' => 'Approved', 'class' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20'],
	                                'rejected' => ['label' => 'Rejected', 'class' => 'bg-rose-50 text-rose-700 ring-rose-600/20'],
	                                'pending' => ['label' => 'Pending', 'class' => 'bg-amber-50 text-amber-700 ring-amber-600/20'],
	                                default => ['label' => 'Missing', 'class' => 'bg-zinc-50 text-zinc-600 ring-zinc-600/10 dark:bg-zinc-800 dark:text-zinc-300'],
	                            };
	                        @endphp
	                        <div class="rounded-xl border border-dashed border-gray-200 dark:border-gray-700 p-3 flex flex-col justify-between gap-2 bg-gray-50/50 dark:bg-slate-900/50">
	                            <div class="flex items-center justify-between">
	                                <span class="text-xs font-medium text-gray-700 dark:text-gray-300">{{ $doc['label'] }}</span>
	                                <span class="inline-flex items-center rounded-md px-1.5 py-0.5 text-xs font-medium ring-1 ring-inset {{ $badge['class'] }}">
	                                    {{ $badge['label'] }}
	                                </span>
	                            </div>
	                            
	                            <div class="flex items-center justify-between">
	                                <div class="flex items-center gap-2 text-xs text-gray-500">
	                                    <flux:icon name="document-text" class="size-4 text-gray-400" />
	                                    <span class="truncate max-w-[100px]">{{ $uploaded ? basename($doc['doc']->path) : 'Required' }}</span>
	                                </div>
	                                @if($uploaded)
	                                    <flux:button variant="ghost" size="xs" icon="eye" wire:click="openPdfPreview('{{ $doc['doc']->path }}')" />
	                                @endif
	                            </div>
	                        </div>
	                    @endforeach
	                </div>

                <div class="border-t border-gray-100 dark:border-gray-700"></div>

                {{-- Action Footer --}}
	                <div class="flex items-center justify-between pt-2">
	                    <div class="flex items-center gap-2 text-xs text-gray-500">
	                        <span>Last update {{ $student->eligibilityDocs->max('updated_at')?->format('M d') }}</span>
	                    </div>
	                    
	                    @if($status === 'pending')
	                        <div class="flex items-center gap-2">
	                            <flux:button size="sm" variant="danger" wire:click="reject({{ $student->id }})" wire:confirm="Reject this student's eligibility?">Reject</flux:button>
	                            <flux:button size="sm" variant="primary" wire:click="approve({{ $student->id }})" :disabled="! $canApprove">
	                                Approve
	                            </flux:button>
	                        </div>
	                    @endif
	                </div>
	            </div>
	        @empty
            <div class="col-span-full py-12 text-center rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-zinc-900">
                <div class="flex flex-col items-center justify-center gap-2">
                    <flux:icon name="document-magnifying-glass" class="size-10 text-zinc-300" />
                    <p class="text-sm font-medium text-zinc-500">No applications match your search.</p>
                </div>
            </div>
	        @endforelse
	    </div>
	</div>
