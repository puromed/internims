<?php

use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $search = "";
    public string $roleFilter = "all";
    public ?int $editingUserId = null;
    public string $editingRole = "";
    public string $editingStudentId = "";
    public string $editingCourseCode = "";

    public bool $showCreateUserModal = false;
    public string $createName = "";
    public string $createEmail = "";
    public string $createRole = "faculty";
    public string $createPassword = "";
    public string $createPasswordConfirmation = "";

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedRoleFilter(): void
    {
        $this->resetPage();
    }

    public function startEditing(int $userId): void
    {
        $user = User::findOrFail($userId);

        $this->editingUserId = $user->id;
        $this->editingRole = $user->role;
        $this->editingStudentId = $user->student_id ?? "";
        $this->editingCourseCode = $user->course_code ?? "";
    }

    public function cancelEditing(): void
    {
        $this->editingUserId = null;
        $this->editingRole = "";
        $this->editingStudentId = "";
        $this->editingCourseCode = "";
    }

    public function openCreateUserModal(): void
    {
        $this->resetCreateUserForm();
        $this->showCreateUserModal = true;
    }

    public function closeCreateUserModal(): void
    {
        $this->showCreateUserModal = false;
        $this->resetCreateUserForm();
    }

    public function createUser(): void
    {
        if (!auth()->user()?->isAdmin()) {
            abort(403);
        }

        $validated = $this->validate(
            [
                "createName" => ["required", "string", "max:255"],
                "createEmail" => [
                    "required",
                    "email",
                    "max:255",
                    "unique:users,email",
                ],
                "createRole" => ["required", "in:faculty,admin"],
                "createPassword" => ["required", "string", "min:8"],
                "createPasswordConfirmation" => [
                    "required",
                    "same:createPassword",
                ],
            ],
            [
                "createRole.in" =>
                    "You can only create faculty or admin users here.",
                "createPasswordConfirmation.same" =>
                    "Password confirmation does not match.",
            ],
        );

        $user = User::query()->create([
            "name" => trim($validated["createName"]),
            "email" => mb_strtolower(trim($validated["createEmail"])),
            "role" => $validated["createRole"],
            "password" => $validated["createPassword"],
        ]);

        $user->forceFill(["email_verified_at" => now()])->save();

        session()->flash("status", "User created successfully.");
        $this->dispatch("notify", message: "User created.");

        $this->closeCreateUserModal();
        $this->resetPage();
    }

    protected function resetCreateUserForm(): void
    {
        $this->reset([
            "createName",
            "createEmail",
            "createRole",
            "createPassword",
            "createPasswordConfirmation",
        ]);

        $this->createRole = "faculty";
    }

    public function updateUser(): void
    {
        if (! $this->editingUserId) {
            return;
        }

        $user = User::findOrFail($this->editingUserId);

        // Prevent demoting yourself
        if ($user->id === auth()->id() && $this->editingRole !== "admin") {
            session()->flash("error", "You cannot change your own role.");
            $this->cancelEditing();
            return;
        }

        $rules = [
            "editingRole" => ["required", "in:student,faculty,admin"],
        ];

        if ($this->editingRole === "student") {
            $rules["editingStudentId"] = [
                "required",
                "digits:10",
                Rule::unique(User::class, "student_id")->ignore($user->id),
            ];
            $rules["editingCourseCode"] = [
                "required",
                Rule::in(array_keys(User::courseOptions())),
            ];
        }

        $validated = $this->validate(
            $rules,
            [
                "editingCourseCode.in" => "Please select a valid course.",
            ],
        );

        $user->role = $validated["editingRole"];

        if ($validated["editingRole"] === "student") {
            $user->student_id = $validated["editingStudentId"];
            $user->course_code = $validated["editingCourseCode"];
        }

        $user->save();

        session()->flash(
            "status",
            "User updated successfully.",
        );
        $this->dispatch("notify", message: "User updated.");
        $this->cancelEditing();
    }

    public function with(): array
    {
        $query = User::query()->latest();

        if ($this->roleFilter !== "all") {
            $query->where("role", $this->roleFilter);
        }

        if ($this->search !== "") {
            $query->where(function ($q) {
                $q->where("name", "like", "%" . $this->search . "%")->orWhere(
                    "email",
                    "like",
                    "%" . $this->search . "%",
                );
            });
        }

        return [
            "users" => $query->paginate(12),
            "counts" => [
                "all" => User::count(),
                "student" => User::where("role", "student")->count(),
                "faculty" => User::where("role", "faculty")->count(),
                "admin" => User::where("role", "admin")->count(),
            ],
        ];
    }
};
?>

<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Admin</p>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-100">User Management</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">View and manage user roles.</p>
        </div>
        <flux:button icon="plus" wire:click="openCreateUserModal">Add User</flux:button>
    </div>

    {{-- Create User Modal --}}
    <flux:modal wire:model="showCreateUserModal" class="max-w-lg">
        <div class="space-y-5">
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Add User</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Create a faculty or admin account. Students register through the normal registration flow.
                </p>
            </div>

            <div class="space-y-4">
                <flux:input wire:model.defer="createName" label="Name" placeholder="e.g. Dr. Nur" />
                @error('createName') <p class="text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror

                <flux:input wire:model.defer="createEmail" label="Email" placeholder="e.g. nur@example.com" />
                @error('createEmail') <p class="text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Role</label>
                    <select wire:model.defer="createRole" class="w-full rounded-xl border-gray-200 px-3 py-2 text-sm text-gray-700 focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-slate-800 dark:text-gray-100">
                        <option value="faculty">Faculty</option>
                        <option value="admin">Admin</option>
                    </select>
                    @error('createRole') <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
                </div>

                <flux:input wire:model.defer="createPassword" type="password" label="Password" />
                @error('createPassword') <p class="text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror

                <flux:input wire:model.defer="createPasswordConfirmation" type="password" label="Confirm Password" />
                @error('createPasswordConfirmation') <p class="text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
            </div>

            <div class="flex justify-end gap-2">
                <flux:button wire:click="closeCreateUserModal" variant="ghost">Cancel</flux:button>
                <flux:button wire:click="createUser" variant="primary" wire:loading.attr="disabled" wire:target="createUser">
                    Create user
                </flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Stats Overview --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="flex flex-col gap-1 rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-zinc-900">
            <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Total Users</span>
            <span class="text-2xl font-bold text-gray-900 dark:text-white">{{ $counts['all'] }}</span>
        </div>
        <div class="flex flex-col gap-1 rounded-xl border border-indigo-200 bg-indigo-50/50 p-4 shadow-sm dark:border-indigo-500/20 dark:bg-indigo-500/5">
            <span class="text-xs font-medium text-indigo-600 dark:text-indigo-400">Students</span>
            <span class="text-2xl font-bold text-indigo-700 dark:text-indigo-500">{{ $counts['student'] }}</span>
        </div>
        <div class="flex flex-col gap-1 rounded-xl border border-emerald-200 bg-emerald-50/50 p-4 shadow-sm dark:border-emerald-500/20 dark:bg-emerald-500/5">
            <span class="text-xs font-medium text-emerald-600 dark:text-emerald-400">Faculty</span>
            <span class="text-2xl font-bold text-emerald-700 dark:text-emerald-500">{{ $counts['faculty'] }}</span>
        </div>
        <div class="flex flex-col gap-1 rounded-xl border border-purple-200 bg-purple-50/50 p-4 shadow-sm dark:border-purple-500/20 dark:bg-purple-500/5">
            <span class="text-xs font-medium text-purple-600 dark:text-purple-400">Admins</span>
            <span class="text-2xl font-bold text-purple-700 dark:text-purple-500">{{ $counts['admin'] }}</span>
        </div>
    </div>

    {{-- Filters --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-wrap gap-2">
            <flux:button size="sm" variant="{{ $roleFilter === 'all' ? 'filled' : 'subtle' }}" wire:click="$set('roleFilter', 'all')" icon="users">All</flux:button>
            <flux:button size="sm" variant="{{ $roleFilter === 'student' ? 'filled' : 'subtle' }}" wire:click="$set('roleFilter', 'student')" icon="academic-cap">Students</flux:button>
            <flux:button size="sm" variant="{{ $roleFilter === 'faculty' ? 'filled' : 'subtle' }}" wire:click="$set('roleFilter', 'faculty')" icon="user">Faculty</flux:button>
            <flux:button size="sm" variant="{{ $roleFilter === 'admin' ? 'filled' : 'subtle' }}" wire:click="$set('roleFilter', 'admin')" icon="shield-check">Admins</flux:button>
        </div>
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Search users..." class="w-full sm:w-64" />
    </div>

    {{-- Users Grid --}}
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
        @forelse($users as $user)
            @php
                $roleBadgeClass = $user->role === 'faculty'
                    ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20'
                    : ($user->role === 'admin'
                        ? 'bg-purple-50 text-purple-700 ring-purple-600/20'
                        : 'bg-indigo-50 text-indigo-700 ring-indigo-600/20');
                $roleIcon = $user->role === 'faculty'
                    ? 'user'
                    : ($user->role === 'admin' ? 'shield-check' : 'academic-cap');
            @endphp

            <div class="flex flex-col justify-between rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-zinc-900">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-zinc-100 text-sm font-semibold text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                            {{ $user->initials() }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $user->name }}</p>
                            <p class="truncate text-xs text-gray-500 dark:text-gray-400">{{ $user->email }}</p>
                        </div>
                    </div>
                </div>

                @if($user->role === 'student')
                    <div class="mt-3 flex flex-col gap-1 text-xs text-gray-500 dark:text-gray-400">
                        <div>
                            <span class="font-medium text-gray-700 dark:text-gray-300">Student ID:</span>
                            <span class="text-gray-900 dark:text-gray-100">{{ $user->student_id ?? '—' }}</span>
                        </div>
                        <div>
                            <span class="font-medium text-gray-700 dark:text-gray-300">Course:</span>
                            <span class="text-gray-900 dark:text-gray-100">
                                {{ \App\Models\User::courseOptions()[$user->course_code] ?? $user->course_code ?? '—' }}
                            </span>
                        </div>
                    </div>
                @endif

                <div class="mt-4 border-t border-gray-100 pt-4 dark:border-gray-800">
                    <div class="flex items-center justify-between">
                        @if($editingUserId === $user->id)
                            <div class="flex flex-col gap-3">
                                <div class="flex items-center gap-2">
                                    <select wire:model="editingRole" class="rounded-lg border-gray-300 py-1 text-xs dark:border-gray-600 dark:bg-slate-800 dark:text-gray-100">
                                        <option value="student">Student</option>
                                        <option value="faculty">Faculty</option>
                                        <option value="admin">Admin</option>
                                    </select>
                                    <flux:button size="xs" variant="primary" icon="check" wire:click="updateUser" />
                                    <flux:button size="xs" variant="ghost" icon="x-mark" wire:click="cancelEditing" />
                                </div>

                                @if($editingRole === 'student')
                                    <div class="grid gap-2">
                                        <div>
                                            <flux:input wire:model.defer="editingStudentId" label="Student ID" />
                                            @error('editingStudentId') <p class="text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
                                        </div>
                                        <div>
                                            <flux:select wire:model.defer="editingCourseCode" label="Course">
                                                @foreach (\App\Models\User::courseOptions() as $code => $label)
                                                    <flux:select.option value="{{ $code }}">{{ $code }} - {{ $label }}</flux:select.option>
                                                @endforeach
                                            </flux:select>
                                            @error('editingCourseCode') <p class="text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @else
                            <span class="inline-flex items-center gap-1.5 rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset {{ $roleBadgeClass }}">
                                <flux:icon name="{{ $roleIcon }}" class="size-3" />
                                {{ ucfirst($user->role) }}
                            </span>
                            @if($user->id !== auth()->id())
                                <flux:button size="xs" variant="ghost" icon="pencil" wire:click="startEditing({{ $user->id }})" />
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full rounded-xl border border-gray-200 bg-white p-12 text-center dark:border-gray-700 dark:bg-zinc-900">
                <flux:icon name="users" class="mx-auto size-12 text-zinc-300" />
                <p class="mt-4 text-sm font-medium text-zinc-500">No users found matching your filters.</p>
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    <div class="mt-4">
        {{ $users->links() }}
    </div>
</div>
