<?php

use App\Models\Application;
use App\Models\ProposedCompany;
use App\Models\Internship;
use App\Models\User;
use App\Notifications\ProposedCompanySubmittedNotification;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component {
    public ?Application $application = null;
    public ?Internship $internship = null;
    public string $industrySupervisorName = "";
    public array $proposals = [
        ["name" => "", "website" => "", "address" => "", "job_scope" => ""],
        ["name" => "", "website" => "", "address" => "", "job_scope" => ""],
    ];
    public function mount(): void
    {
        $user = Auth::user();
        $this->application = $user->applications()->latest()->first();
        $this->internship = $this->application
            ?->internship()
            ->with("facultySupervisor")
            ->first();
        $this->industrySupervisorName =
            (string) ($this->internship?->supervisor_name ?? "");
        // Load existing proposals if application exists
        if ($this->application) {
            $existingProposals = $this->application->proposedCompanies()->get();
            if ($existingProposals->count() > 0) {
                // Pre-fill from database
                $this->proposals = $existingProposals
                    ->map(
                        fn($p) => [
                            "id" => $p->id,
                            "name" => $p->name,
                            "website" => $p->website ?? "",
                            "address" => $p->address ?? "",
                            "job_scope" => $p->job_scope,
                            "status" => $p->status,
                        ],
                    )
                    ->toArray();
            }
        }
    }

    public function saveInternshipDetails(): void
    {
        if (!$this->internship) {
            return;
        }

        if ((int) $this->internship->user_id !== (int) Auth::id()) {
            abort(403);
        }

        $this->validate([
            "industrySupervisorName" => ["nullable", "string", "max:255"],
        ]);

        $this->internship->update([
            "supervisor_name" => filled($this->industrySupervisorName)
                ? $this->industrySupervisorName
                : null,
        ]);

        $this->dispatch("start-toast", message: "Internship details saved.");
        $this->mount();
    }

    public function confirmPlacement(int $proposalId): void
    {
        $application = $this->application;

        if (!$application) {
            $this->addError("confirm", "No application found");
            return;
        }

        if ($application->internship()->exists()) {
            $this->addError(
                "confirm",
                "You have already confirmed your placement",
            );
            return;
        }

        $proposal = $application
            ->proposedCompanies()
            ->whereKey($proposalId)
            ->where("status", "approved")
            ->first();

        if (!$proposal) {
            $this->addError("confirm", "That company is not approved");
            return;
        }

        $application
            ->forceFill([
                "status" => "approved",
                "company_name" => $proposal->name,
            ])
            ->save();

        $application->internship()->create([
            "user_id" => Auth::id(),
            "company_name" => $proposal->name,
            "status" => "pending",
            "start_date" => now()->addWeeks(2),
        ]);

        $this->mount();
        $this->dispatch(
            "start-toast",
            message: "Placement confirmed successfully.",
        );
    }

    protected function syncInternshipFromApplication(): void
    {
        if (!$this->application || $this->application->status !== "approved") {
            return;
        }

        // Create/update internship when application is approved
        $this->internship = Internship::updateOrCreate(
            [
                "user_id" => Auth::id(),
                "application_id" => $this->application->id,
            ],
            [
                "company_name" => $this->application->company_name,
                "status" => "pending",
                "start_date" => now()->addWeeks(2), // Default start date
            ],
        );
    }

    public function submit(): void
    {
        $this->validate(
            [
                "proposals" => "required|array|size:2",
                "proposals.*.name" => "required|string|max:255",
                "proposals.*.job_scope" => "required|string|max:1000",
                "proposals.*.website" => "nullable|url|max:255",
                "proposals.*.address" => "nullable|string|max:500",
            ],
            [
                "proposals.*.name.required" => "Company name is required.",
                "proposals.*.job_scope.required" => "Job scope is required.",
            ],
        );

        $hadProposalsBefore = (bool) $this->application
            ?->proposedCompanies()
            ->exists();

        // Create or update application
        $this->application = Application::updateOrCreate(
            ["user_id" => Auth::id()],
            ["status" => "submitted", "submitted_at" => now()],
        );

        foreach ($this->proposals as $proposalData) {
            $data = [
                "name" => $proposalData["name"],
                "website" => $proposalData["website"] ?? null,
                "address" => $proposalData["address"] ?? null,
                "job_scope" => $proposalData["job_scope"],
                "status" => "pending",
                "admin_remarks" => null,
            ];

            if (isset($proposalData["id"])) {
                ProposedCompany::where("id", $proposalData["id"])->update(
                    $data,
                );
            } else {
                $this->application->proposedCompanies()->create($data);
            }
        }

        if (!$hadProposalsBefore) {
            $admins = User::query()->where("role", "admin")->get();

            foreach ($admins as $admin) {
                $admin->notify(
                    new ProposedCompanySubmittedNotification(
                        student: Auth::user(),
                        application: $this->application,
                        companyNames: collect($this->proposals)
                            ->pluck("name")
                            ->filter()
                            ->values()
                            ->all(),
                    ),
                );
            }
        }

        // Refresh state
        $this->mount();

        $this->dispatch(
            "start-toast",
            message: "Placement proposals submitted successfully.",
        );
    }
};
?>

<div class="space-y-8">
    <div>
        <h2 class="text-xl font-bold text-gray-900 dark:text-white">Placement</h2>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Submit your placement details and track approval.</p>
    </div>

    @php
        $status = $internship?->status ?? $application?->status ?? 'draft';
        $statusMap = [
            'draft'     => ['label' => 'Draft',     'color' => 'bg-gray-100 text-gray-700 dark:bg-zinc-800 dark:text-gray-300', 'icon' => 'pencil'],
            'submitted' => ['label' => 'Submitted', 'color' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-200', 'icon' => 'clock'],
            'approved'  => ['label' => 'Approved',  'color' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-200', 'icon' => 'check-circle'],
            'rejected'  => ['label' => 'Rejected',  'color' => 'bg-rose-100 text-rose-800 dark:bg-rose-900/50 dark:text-rose-200', 'icon' => 'x-circle'],
            'pending'   => ['label' => 'Placement Confirmed',   'color' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-200', 'icon' => 'clock'],
            'active'    => ['label' => 'Internship Active',   'color' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-200', 'icon' => 'check-circle'],
        ];
        $stat = $statusMap[$status] ?? $statusMap['draft'];
    @endphp

    <div class="flex items-center justify-between rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-zinc-900">
        <div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Current Status</p>
            <div class="mt-1">
                <p class="text-xl font-bold text-gray-900 dark:text-white">{{ $stat['label'] }}</p>
                @if($internship)
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Company: {{ $internship->company_name ?? 'TBD' }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Industry Supervisor: {{ $internship->supervisor_name ?? 'TBD' }}
                    </p>
                    @if($internship->facultySupervisor)
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Faculty Supervisor: {{ $internship->facultySupervisor->name }}</p>
                    @endif
                @endif
            </div>
        </div>
        <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-sm font-medium {{ $stat['color'] }}">
            <flux:icon name="{{ $stat['icon'] }}" class="size-4" />
            {{ $stat['label'] }}
        </span>
    </div>

    @php
        $approvedProposals = collect($proposals)
            ->filter(fn (array $proposal): bool => ($proposal['status'] ?? null) === 'approved')
            ->values();

        $canConfirmPlacement = $application && ! $internship && $approvedProposals->isNotEmpty();
    @endphp

    @if($internship)
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-zinc-900 space-y-4">
            <div>
                <flux:heading size="lg">Internship Details</flux:heading>
                <flux:subheading>Provide your industry supervisor details for record keeping.</flux:subheading>
            </div>

            <flux:input
                wire:model.defer="industrySupervisorName"
                label="Industry Supervisor Name"
                placeholder="e.g. Siti Nur Aisyah"
            />

            <div class="flex justify-end">
                <flux:button
                    variant="primary"
                    wire:click="saveInternshipDetails"
                    wire:loading.attr="disabled"
                    wire:target="saveInternshipDetails"
                >
                    Save details
                </flux:button>
            </div>
        </div>
    @endif

    @if($canConfirmPlacement)
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-6 dark:border-emerald-900/40 dark:bg-emerald-950/30">
            <div class="flex items-start justify-between gap-4">
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <flux:icon name="check-circle" class="size-5 text-emerald-600 dark:text-emerald-300" />
                        <h3 class="text-base font-semibold text-emerald-900 dark:text-emerald-100">Confirm Placement</h3>
                    </div>
                    <p class="text-sm text-emerald-800/90 dark:text-emerald-200/90">
                        An admin approved {{ $approvedProposals->count() > 1 ? 'multiple companies' : 'a company' }}. Confirm your final choice to create your internship record and unlock logbooks.
                    </p>

                    @error('confirm')
                        <p class="text-sm font-medium text-rose-700 dark:text-rose-300">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mt-4 space-y-3">
                @foreach($approvedProposals as $approvedProposal)
                    <div
                        wire:key="approved-proposal-{{ $approvedProposal['id'] ?? $loop->index }}"
                        class="flex items-center justify-between gap-4 rounded-lg bg-white p-4 ring-1 ring-emerald-200/60 dark:bg-zinc-900 dark:ring-emerald-900/40"
                    >
                        <div>
                            <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $approvedProposal['name'] ?? 'Approved company' }}</p>
                            @if(! empty($approvedProposal['website']))
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $approvedProposal['website'] }}</p>
                            @endif
                        </div>

                        @if(! empty($approvedProposal['id']))
                            <flux:button
                                variant="primary"
                                wire:click="confirmPlacement({{ (int) $approvedProposal['id'] }})"
                                wire:loading.attr="disabled"
                                wire:target="confirmPlacement({{ (int) $approvedProposal['id'] }})"
                            >
                                Confirm
                            </flux:button>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if($application && $application->status === 'submitted' && ! $internship && $approvedProposals->isEmpty())
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-200">
            <div class="flex items-start gap-3">
                <flux:icon name="clock" class="mt-0.5 size-5 shrink-0" />
                <span><strong>Pending Approval:</strong> Your placement is under review. You'll be notified once it's approved and you can start submitting logbooks.</span>
            </div>
        </div>
    @endif

    <div class="space-y-6">
        @foreach($proposals as $index => $proposal)
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-zinc-900">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Company Choice {{ $index + 1 }}</h3>
                    @if(isset($proposal['status']) && $proposal['status'] !== 'pending')
                        @php
                            $pStat = match($proposal['status']) {
                                'approved' => ['label' => 'Approved', 'color' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-200', 'icon' => 'check-circle'],
                                'rejected' => ['label' => 'Rejected', 'color' => 'bg-rose-100 text-rose-800 dark:bg-rose-900/50 dark:text-rose-200', 'icon' => 'x-circle'],
                                default => ['label' => 'Pending', 'color' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-200', 'icon' => 'clock'],
                            };
                        @endphp
                        <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold {{ $pStat['color'] }}">
                            <flux:icon name="{{ $pStat['icon'] }}" class="size-3.5" />
                            {{ $pStat['label'] }}
                        </span>
                    @endif
                </div>

                @php
                    // Proposal is locked if it's already pending or approved.
                    // It is unlocked (editable) only if it's new (no status) or rejected.
                    $pStatus = $proposal['status'] ?? 'new';
                    $isProposalLocked = in_array($pStatus, ['pending', 'approved']);

                    // However, if the entire application is NOT submitted yet (draft), everything is editable.
                    // But here application status is likely 'submitted'.
                    // So we rely on proposal status.
                    if (!$application || $application->status === 'draft') {
                        $isProposalLocked = false;
                    }
                @endphp

                <div class="grid gap-6 md:grid-cols-2">
                    {{-- Company Name --}}
                    <flux:input
                        wire:model="proposals.{{ $index }}.name"
                        label="Company Name"
                        placeholder="e.g. Tech Solutions Inc."
                        :disabled="$isProposalLocked"
                    />

                    {{-- Website --}}
                    <flux:input
                        wire:model="proposals.{{ $index }}.website"
                        label="Website"
                        placeholder="https://example.com"
                        type="url"
                        :disabled="$isProposalLocked"
                    />

                    {{-- Address --}}
                    <div class="md:col-span-2">
                        <flux:input
                            wire:model="proposals.{{ $index }}.address"
                            label="Address"
                            placeholder="Full company address"
                            :disabled="$isProposalLocked"
                        />
                    </div>

                    {{-- Job Scope --}}
                    <div class="md:col-span-2">
                        <flux:textarea
                            wire:model="proposals.{{ $index }}.job_scope"
                            label="Job Scope / Description"
                            placeholder="Describe the role, responsibilities, and how it relates to your field of study..."
                            rows="4"
                            :disabled="$isProposalLocked"
                        />
                    </div>
                </div>
            </div>
        @endforeach

        {{-- Submit Button --}}
        <div class="flex justify-end">
            @php
                // Check if ANY proposal is editable (i.e., not locked)
                $anyEditable = false;
                foreach($proposals as $p) {
                    $pStat = $p['status'] ?? 'new';
                    if (!in_array($pStat, ['pending', 'approved'])) {
                        $anyEditable = true;
                        break;
                    }
                }

                // If application is draft, it's editable
                if (!$application || $application->status === 'draft') {
                    $anyEditable = true;
                }

                // Disable if nothing to edit
                $isDisabled = !$anyEditable;
            @endphp
            <flux:button
                wire:click="submit"
                variant="primary"
                class="w-full sm:w-auto"
                :disabled="$isDisabled"
            >
                {{ $isDisabled ? 'Awaiting Review' : 'Submit Proposals' }}
            </flux:button>
        </div>
    </div>
</div>
