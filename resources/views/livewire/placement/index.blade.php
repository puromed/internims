<?php

use App\Models\Application;
use App\Models\Internship;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component {
    public ?Application $application = null;
    public ?Internship $internship = null;

    public string $company_name = '';
    public string $position = '';
 
    public function mount(): void
    {
        $user = Auth::user();
        $this->application = $user->applications()->latest()->first();
        $this->internship  = $user->internships()->latest()->first();

        if ($this->application) {
            $this->company_name = $this->application->company_name ?? '';
            $this->position     = $this->application->position ?? '';
        }

        // Sync internship if application is approved
        $this->syncInternshipFromApplication();
    }

    protected function syncInternshipFromApplication(): void
    {
        if (!$this->application || $this->application->status !== 'approved') {
            return;
        }

        // Create/update internship when application is approved
        $this->internship = Internship::updateOrCreate(
            ['user_id' => Auth::id(), 'application_id' => $this->application->id],
            [
                'company_name' => $this->application->company_name,
                'status' => 'pending',
                'start_date' => now()->addWeeks(2), // Default start date
            ]
        );
    }

    public function submit(): void
    {
        $data = $this->validate([
            'company_name' => 'required|string|max:255',
            'position'     => 'required|string|max:255',
        ]);

        $this->application = Application::updateOrCreate(
            ['user_id' => Auth::id()],
            array_merge($data, ['status' => 'submitted', 'submitted_at' => now()])
        );

        session()->flash('status', 'Placement submitted for approval.');
        $this->dispatch('notify', message: 'Placement submitted.');
    }
}; ?>

<div class="space-y-8">
    <div>
        <h2 class="text-3xl font-bold text-gray-900">Placement</h2>
        <p class="mt-1 text-sm text-gray-500">Submit your placement details and track approval.</p>
    </div>

    @php
        $status = $internship?->status ?? $application?->status ?? 'draft';
        $statusMap = [
            'draft'     => ['label' => 'Draft',     'class' => 'bg-gray-100 text-gray-700'],
            'submitted' => ['label' => 'Submitted', 'class' => 'bg-amber-100 text-amber-800'],
            'approved'  => ['label' => 'Approved',  'class' => 'bg-green-100 text-green-800'],
            'rejected'  => ['label' => 'Rejected',  'class' => 'bg-rose-100 text-rose-800'],
            'pending'   => ['label' => 'Pending',   'class' => 'bg-amber-100 text-amber-800'],
        ];
        $stat = $statusMap[$status] ?? $statusMap['draft'];
    @endphp

    <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-200 p-6 flex items-center justify-between">
        <div>
            <p class="text-sm text-gray-500">Current Status</p>
            <p class="text-xl font-semibold text-gray-900 capitalize">{{ $status }}</p>
            @if($internship)
                <p class="text-sm text-gray-500 mt-1">Supervisor: {{ $internship->supervisor_name ?? 'TBD' }}</p>
                <p class="text-sm text-gray-500">Company: {{ $internship->company_name ?? $application?->company_name ?? '—' }}</p>
            @endif
        </div>
        <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $stat['class'] }}">
            {{ $stat['label'] }}
        </span>
    </div>

    @if($application && $application->status === 'submitted' && !$internship)
        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            <div class="flex items-center gap-2">
                <i data-lucide="clock" class="h-4 w-4"></i>
                <span><strong>Pending Approval:</strong> Your placement is under review. You'll be notified once it's approved and you can start submitting logbooks.</span>
            </div>
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900">Placement Details</h3>
            <p class="text-sm text-gray-500 mb-4">Submit your placement to unlock logbooks.</p>

            <div class="space-y-4">
                <div>
                    <label class="text-sm font-medium text-gray-700">Company Name</label>
                    <input type="text" wire:model.defer="company_name"
                        {{ ($application && $application->status === 'submitted' && !$internship) ? 'disabled' : '' }}
                        class="mt-1 block w-full rounded-lg border border-gray-200 bg-white text-gray-900 focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-100 disabled:cursor-not-allowed">
                    @error('company_name')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700">Position</label>
                    <input type="text" wire:model.defer="position"
                        {{ ($application && $application->status === 'submitted' && !$internship) ? 'disabled' : '' }}
                        class="mt-1 block w-full rounded-lg border border-gray-200 bg-white text-gray-900 focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-100 disabled:cursor-not-allowed">
                    @error('position')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
                <div class="flex justify-end">
                    <button wire:click="submit"
                        {{ ($application && $application->status === 'submitted' && !$internship) ? 'disabled' : '' }}
                        class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:bg-gray-400 disabled:cursor-not-allowed">
                        {{ ($application && $application->status === 'submitted' && !$internship) ? 'Pending Approval' : 'Submit placement' }}
                    </button>
                </div>
            </div>
        </div>

        <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900">Summary</h3>
            <div class="mt-4 space-y-2 text-sm text-gray-700">
                <div class="flex justify-between"><span>Company</span><span class="font-semibold">{{ $application?->company_name ?? '—' }}</span></div>
                <div class="flex justify-between"><span>Position</span><span class="font-semibold">{{ $application?->position ?? '—' }}</span></div>
                <div class="flex justify-between"><span>Submitted</span><span>{{ optional($application?->submitted_at)->diffForHumans() ?? '—' }}</span></div>
            </div>
        </div>
    </div>
</div>
