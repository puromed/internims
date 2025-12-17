<?php

use App\Models\ProposedCompany;
use App\Notifications\ProposedCompanyStatusNotification;
use Livewire\Volt\Component;

new class extends Component {
    public string $search = '';
    public string $statusFilter = 'all';

    // Remarks Modal State
    public bool $showRemarksModal = false;
    public ?int $rejectingProposalId = null;
    public string $adminRemarks = '';

    public function updatedSearch(): void {}
    public function updatedStatusFilter(): void {}

    public function approve(int $proposalId): void
    {
        $proposal = ProposedCompany::query()
            ->with('application.user')
            ->findOrFail($proposalId);
        $previousStatus = $proposal->status;

        $proposal->update([
            'status' => 'approved',
            'admin_remarks' => null,
        ]);

        if ($previousStatus !== 'approved') {
            $student = $proposal->application?->user;

            if ($student) {
                $student->notify(new ProposedCompanyStatusNotification(
                    proposal: $proposal,
                    status: 'approved',
                    remark: null,
                ));
            }
        }

        $this->dispatch('start-toast', message: 'Company proposal approved.');
    }

    public function openRejectModal(int $proposalId): void
    {
        $this->rejectingProposalId = $proposalId;
        $this->adminRemarks = '';
        $this->showRemarksModal = true;
    }

    public function confirmReject(): void
    {
        if (! $this->rejectingProposalId) {
            return;
        }

        $proposal = ProposedCompany::query()
            ->with('application.user')
            ->findOrFail($this->rejectingProposalId);

        $previousStatus = $proposal->status;    

        $proposal->update([
            'status' => 'rejected',
            'admin_remarks' => $this->adminRemarks ?: null,
        ]);

        if ($previousStatus !== 'rejected') {
            $student = $proposal->application?->user;

            if ($student) {
                $student->notify(new ProposedCompanyStatusNotification(
                    proposal: $proposal,
                    status: 'rejected',
                    remark: $proposal->admin_remarks,
                ));
            }
        }

       

        $this->showRemarksModal = false;
        $this->rejectingProposalId = null;
        $this->adminRemarks = '';

        $this->dispatch('start-toast', message: 'Company proposal rejected.');
    }

    public function with(): array
    {
        $query = ProposedCompany::query()
            ->with(['application.user'])
            ->latest();

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhereHas('application.user', fn($u) => $u->where('name', 'like', '%' . $this->search . '%'));
            });
        }

        $proposals = $query->get();

        return [
            'proposals' => $proposals,
            'counts' => [
                'all' => ProposedCompany::count(),
                'pending' => ProposedCompany::where('status', 'pending')->count(),
                'approved' => ProposedCompany::where('status', 'approved')->count(),
                'rejected' => ProposedCompany::where('status', 'rejected')->count(),
            ],
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
        <flux:icon name="check-circle" class="size-5 text-emerald-500" />
        <p class="text-sm font-semibold text-gray-900" x-text="message"></p>
    </div>

    {{-- Rejection Remarks Modal --}}
    <flux:modal wire:model="showRemarksModal" class="max-w-md">
        <div class="space-y-4">
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Reject Company Proposal</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Provide a reason for rejection (optional but recommended).</p>
            </div>

            <flux:textarea 
                wire:model="adminRemarks" 
                label="Remarks" 
                placeholder="e.g., Company does not align with program requirements..."
                rows="3"
            />

            <div class="flex justify-end gap-2 pt-2">
                <flux:button wire:click="$set('showRemarksModal', false)">Cancel</flux:button>
                <flux:button variant="danger" wire:click="confirmReject">Confirm Rejection</flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Header --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Admin</p>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Company Proposals</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Review and approve student internship company proposals.</p>
        </div>
        <div class="flex items-center gap-2">
            <flux:button icon="arrow-down-tray">Export</flux:button>
        </div>
    </div>

    {{-- Stats Overview --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="flex flex-col gap-1 rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-zinc-900">
            <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Total Proposals</span>
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
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Search company or student" class="w-full sm:w-64" />
    </div>

    {{-- Proposals Grid --}}
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        @forelse($proposals as $proposal)
            @php
                $user = $proposal->application?->user;
                $initials = $user ? collect(explode(' ', $user->name))->map(fn($w) => strtoupper(substr($w, 0, 1)))->take(2)->join('') : 'N/A';
                
                $statusConfig = [
                    'pending' => ['label' => 'Pending', 'color' => 'bg-amber-50 text-amber-600 ring-amber-100', 'icon' => 'clock'],
                    'approved' => ['label' => 'Approved', 'color' => 'bg-emerald-50 text-emerald-600 ring-emerald-100', 'icon' => 'check-circle'],
                    'rejected' => ['label' => 'Rejected', 'color' => 'bg-rose-50 text-rose-600 ring-rose-100', 'icon' => 'x-circle'],
                ];
                $style = $statusConfig[$proposal->status] ?? $statusConfig['pending'];
            @endphp

            <div class="space-y-4 rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-zinc-900">
                {{-- Card Header: Student Info --}}
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center text-sm font-semibold text-zinc-700 dark:text-zinc-300">
                            {{ $initials }}
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $user?->name ?? 'Unknown' }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $user?->email ?? '' }}</p>
                        </div>
                    </div>
                    <span class="inline-flex items-center gap-1.5 rounded-full px-2 py-1 text-xs font-medium ring-1 ring-inset {{ $style['color'] }}">
                        <flux:icon name="{{ $style['icon'] }}" class="size-3.5" />
                        {{ $style['label'] }}
                    </span>
                </div>

                {{-- Company Details --}}
                <div class="space-y-3 rounded-lg bg-gray-50 dark:bg-zinc-800/50 p-4">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Company</p>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $proposal->name }}</p>
                        </div>
                        @if($proposal->website)
                            <a href="{{ $proposal->website }}" target="_blank" class="text-xs text-indigo-600 hover:underline dark:text-indigo-400">
                                Visit Website →
                            </a>
                        @endif
                    </div>

                    @if($proposal->address)
                        <div>
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Address</p>
                            <p class="text-sm text-gray-700 dark:text-gray-300">{{ $proposal->address }}</p>
                        </div>
                    @endif

                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Job Scope</p>
                        <p class="text-sm text-gray-700 dark:text-gray-300 line-clamp-3">{{ $proposal->job_scope }}</p>
                    </div>
                </div>

                {{-- Admin Remarks (if rejected) --}}
                @if($proposal->status === 'rejected' && $proposal->admin_remarks)
                    <div class="rounded-lg bg-rose-50 dark:bg-rose-900/20 p-3 border border-rose-100 dark:border-rose-800">
                        <p class="text-xs font-medium text-rose-600 dark:text-rose-400">Rejection Reason</p>
                        <p class="text-sm text-rose-700 dark:text-rose-300">{{ $proposal->admin_remarks }}</p>
                    </div>
                @endif

                <div class="border-t border-gray-100 dark:border-gray-700"></div>

                {{-- Action Footer --}}
                <div class="flex items-center justify-between pt-2">
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                        Submitted {{ $proposal->created_at->diffForHumans() }}
                    </span>
                    
                    @if($proposal->status === 'pending')
                        <div class="flex items-center gap-2">
                            <flux:button size="sm" variant="danger" wire:click="openRejectModal({{ $proposal->id }})">Reject</flux:button>
                            <flux:button size="sm" variant="primary" wire:click="approve({{ $proposal->id }})">Approve</flux:button>
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="col-span-full py-12 text-center rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-zinc-900">
                <div class="flex flex-col items-center justify-center gap-2">
                    <flux:icon name="building-office-2" class="size-10 text-zinc-300" />
                    <p class="text-sm font-medium text-zinc-500">No company proposals found.</p>
                    <p class="text-xs text-zinc-400">Proposals will appear here when students submit their internship company choices.</p>
                </div>
            </div>
        @endforelse
    </div>
</div>
