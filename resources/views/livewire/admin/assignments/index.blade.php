<?php

use App\Models\User;
use App\Models\Internship;
use Livewire\Volt\Component;

new class extends Component {
    public string $search = "";
    public ?int $editingInternshipId = null;
    public ?int $selectedFacultyId = null;

    public function startEditing(
        int $internshipId,
        ?int $currentFacultyId,
    ): void {
        $this->editingInternshipId = $internshipId;
        $this->selectedFacultyId = $currentFacultyId;
    }

    public function cancelEditing(): void
    {
        $this->editingInternshipId = null;
        $this->selectedFacultyId = null;
    }

    public function assignFaculty(): void
    {
        if (!$this->editingInternshipId) {
            return;
        }

        $internship = Internship::findOrFail($this->editingInternshipId);
        $internship->update([
            "faculty_supervisor_id" => $this->selectedFacultyId,
            "status" => $this->selectedFacultyId ? "active" : "pending",
        ]);

        $facultyName = $this->selectedFacultyId
            ? User::find($this->selectedFacultyId)?->name ?? "Unknown"
            : "None";

        session()->flash(
            "status",
            "Faculty supervisor updated to {$facultyName}.",
        );
        $this->dispatch("notify", message: "Faculty assignment updated.");
        $this->cancelEditing();
    }

    public function autoAssign(): void
    {
        $facultyIds = User::query()
            ->where("role", "faculty")
            ->pluck("id")
            ->shuffle()
            ->values();

        if ($facultyIds->isEmpty()) {
            $this->dispatch(
                "notify",
                message: "No faculty users found to assign.",
            );
            return;
        }

        $internships = Internship::query()
            ->whereIn("status", ["pending", "active"])
            ->whereNull("faculty_supervisor_id")
            ->inRandomOrder()
            ->get();

        if ($internships->isEmpty()) {
            $this->dispatch(
                "notify",
                message: "All internships already have supervisors assigned.",
            );
            return;
        }

        $facultyCount = $facultyIds->count();

        foreach ($internships as $index => $internship) {
            $internship->update([
                "faculty_supervisor_id" => $facultyIds[$index % $facultyCount],
                "status" => "active",
            ]);
        }

        $this->dispatch("notify", message: "Faculty supervisors assigned.");
    }

    public function with(): array
    {
        $query = Internship::query()
            ->with(["user", "facultySupervisor"])
            ->whereIn("status", ["pending", "active"])
            ->latest();

        if ($this->search !== "") {
            $query->whereHas(
                "user",
                fn($q) => $q->where("name", "like", "%" . $this->search . "%"),
            );
        }

        return [
            "internships" => $query->get(),
            "facultyList" => User::where("role", "faculty")
                ->orderBy("name")
                ->get(),
            "unassignedCount" => Internship::whereIn("status", [
                "pending",
                "active",
            ])
                ->whereNull("faculty_supervisor_id")
                ->count(),
        ];
    }
};
?>

<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Admin</p>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Faculty Assignments</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Assign faculty supervisors to students.</p>
        </div>
        <div class="flex items-center gap-2">
            @if($unassignedCount > 0)
                <flux:button
                    size="sm"
                    variant="primary"
                    icon="sparkles"
                    wire:click="autoAssign"
                    wire:confirm="Auto-assign faculty supervisors to {{ $unassignedCount }} unassigned internship(s)?"
                >
                    Auto assign
                </flux:button>
            @endif
        @if($unassignedCount > 0)
            <div class="flex items-center gap-2 rounded-lg bg-amber-50 px-3 py-1 text-sm font-medium text-amber-600 dark:bg-amber-500/10 dark:text-amber-400">
                <flux:icon name="exclamation-circle" class="size-4" />
                {{ $unassignedCount }} student(s) without supervisor
            </div>
        @endif
        </div>
    </div>

     {{-- Stats Overview --}}
     <div class="grid grid-cols-1 gap-4 sm:grid-cols-3" data-tour="admin-assignments-stats">
        <div class="flex flex-col gap-1 rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-zinc-900">
            <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Internships</span>
            <span class="text-2xl font-bold text-gray-900 dark:text-white">{{ $internships->count() }}</span>
        </div>
        <div class="flex flex-col gap-1 rounded-xl border border-emerald-200 bg-emerald-50/50 p-4 shadow-sm dark:border-emerald-500/20 dark:bg-emerald-500/5">
            <span class="text-xs font-medium text-emerald-600 dark:text-emerald-400">Assigned</span>
            <span class="text-2xl font-bold text-emerald-700 dark:text-emerald-500">{{ $internships->count() - $unassignedCount }}</span>
        </div>
        <div class="flex flex-col gap-1 rounded-xl border border-amber-200 bg-amber-50/50 p-4 shadow-sm dark:border-amber-500/20 dark:bg-amber-500/5">
            <span class="text-xs font-medium text-amber-600 dark:text-amber-400">Unassigned</span>
            <span class="text-2xl font-bold text-amber-700 dark:text-amber-500">{{ $unassignedCount }}</span>
        </div>
    </div>

    {{-- Search --}}
    <div class="flex justify-end">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Search students..." class="w-full sm:w-64" />
    </div>

    {{-- Assignments Table --}}
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-zinc-900" data-tour="admin-assignments-table">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-slate-800/50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">Student</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">Company</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">Faculty Supervisor</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($internships as $internship)
                    @php
                        $initials = collect(explode(' ', $internship->user->name ?? 'U'))->map(fn($w) => strtoupper(substr($w, 0, 1)))->take(2)->join('');
                        $hasSuper = !empty($internship->faculty_supervisor_id);
                    @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-slate-800/50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-3">
                                <div class="h-8 w-8 rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center text-xs font-semibold text-zinc-700 dark:text-zinc-300">
                                    {{ $initials }}
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $internship->user->name ?? 'Unknown' }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $internship->user->email ?? '' }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <p class="text-sm text-gray-900 dark:text-gray-100">{{ $internship->company_name ?? 'Not specified' }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $internship->position ?? '' }}</p>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($editingInternshipId === $internship->id)
                                <select wire:model="selectedFacultyId" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-slate-800 dark:text-gray-100 text-sm py-1.5 w-48">
                                    <option value="">-- No supervisor --</option>
                                    @foreach($facultyList as $faculty)
                                        <option value="{{ $faculty->id }}">{{ $faculty->name }}</option>
                                    @endforeach
                                </select>
                            @else
                                @if($hasSuper)
                                    <div class="flex items-center gap-2">
                                        <div class="h-6 w-6 rounded-full bg-emerald-100 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-400 flex items-center justify-center text-xs font-semibold">
                                            {{ substr($internship->facultySupervisor->name ?? 'F', 0, 1) }}
                                        </div>
                                        <span class="text-sm text-gray-900 dark:text-gray-100">{{ $internship->facultySupervisor->name ?? 'Unknown' }}</span>
                                    </div>
                                @else
                                    <span class="inline-flex items-center gap-1.5 rounded-md bg-amber-50 px-2 py-1 text-xs font-medium text-amber-700 ring-1 ring-inset ring-amber-600/20 dark:bg-amber-400/10 dark:text-amber-400 dark:ring-amber-400/30">
                                        <flux:icon name="exclamation-circle" class="size-3" />
                                        Not assigned
                                    </span>
                                @endif
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            @if($editingInternshipId === $internship->id)
                                <div class="flex items-center justify-end gap-2">
                                    <flux:button size="xs" variant="primary" wire:click="assignFaculty">Save</flux:button>
                                    <flux:button size="xs" variant="ghost" wire:click="cancelEditing">Cancel</flux:button>
                                </div>
                            @else
                                <flux:button size="xs" variant="ghost" icon="pencil" wire:click="startEditing({{ $internship->id }}, {{ $internship->faculty_supervisor_id ?? 'null' }})">
                                    {{ $hasSuper ? 'Change' : 'Assign' }}
                                </flux:button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-12 text-center">
                            <flux:icon name="users" class="mx-auto size-12 text-zinc-300" />
                            <p class="mt-4 text-gray-500 dark:text-gray-400">No active internships found.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
