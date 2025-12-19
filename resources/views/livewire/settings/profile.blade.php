<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;

new class extends Component {
    public string $name = '';
    public string $email = '';
    public string $studentId = '';
    public string $courseCode = '';
    public bool $canEditStudentFields = false;

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $user = Auth::user();

        $this->name = $user->name;
        $this->email = $user->email;
        $this->studentId = $user->student_id ?? '';
        $this->courseCode = $user->course_code ?? '';
        $this->canEditStudentFields = $this->shouldAllowStudentFieldEdit($user);
    }

    protected function shouldAllowStudentFieldEdit(User $user): bool
    {
        return $user->role === 'student'
            && (! $user->student_id || ! $user->course_code);
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();
        $canEditStudentFields = $this->shouldAllowStudentFieldEdit($user);

        $rules = [
            'name' => ['required', 'string', 'max:255'],

            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($user->id)
            ],
        ];

        if ($canEditStudentFields) {
            $rules['studentId'] = [
                'required',
                'digits:10',
                Rule::unique(User::class, 'student_id')->ignore($user->id),
            ];
            $rules['courseCode'] = [
                'required',
                Rule::in(array_keys(User::courseOptions())),
            ];
        }

        $validated = $this->validate($rules);

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        if ($canEditStudentFields) {
            $user->student_id = $validated['studentId'];
            $user->course_code = $validated['courseCode'];
        }

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $this->studentId = $user->student_id ?? '';
        $this->courseCode = $user->course_code ?? '';
        $this->canEditStudentFields = $this->shouldAllowStudentFieldEdit($user);

        $this->dispatch('profile-updated', name: $user->name);
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Profile')" :subheading="__('Update your name and email address')">
        <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-6">
            <flux:input wire:model="name" :label="__('Name')" type="text" required autofocus autocomplete="name" />

            <div>
                <flux:input wire:model="email" :label="__('Email')" type="email" required autocomplete="email" />

                @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail &&! auth()->user()->hasVerifiedEmail())
                    <div>
                        <flux:text class="mt-4">
                            {{ __('Your email address is unverified.') }}

                            <flux:link class="text-sm cursor-pointer" wire:click.prevent="resendVerificationNotification">
                                {{ __('Click here to re-send the verification email.') }}
                            </flux:link>
                        </flux:text>

                        @if (session('status') === 'verification-link-sent')
                            <flux:text class="mt-2 font-medium !dark:text-green-400 !text-green-600">
                                {{ __('A new verification link has been sent to your email address.') }}
                            </flux:text>
                        @endif
                    </div>
                @endif
            </div>

            @if (auth()->user()->role === 'student')
                @php($courseOptions = \App\Models\User::courseOptions())

                @if ($canEditStudentFields)
                    <flux:input
                        wire:model="studentId"
                        :label="__('Student ID')"
                        type="text"
                        required
                        inputmode="numeric"
                        pattern="[0-9]{10}"
                        maxlength="10"
                        placeholder="e.g. 2021542287"
                    />

                    <flux:select wire:model="courseCode" :label="__('Course')" required>
                        @foreach ($courseOptions as $code => $label)
                            <flux:select.option value="{{ $code }}">
                                {{ $code }} - {{ $label }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                @else
                    <div class="rounded-xl border border-gray-200 bg-white p-4 text-sm text-gray-600 shadow-sm dark:border-gray-700 dark:bg-zinc-900 dark:text-gray-300">
                        <div class="flex flex-col gap-2">
                            <div>
                                <span class="font-medium text-gray-900 dark:text-gray-100">{{ __('Student ID') }}:</span>
                                <span>{{ $studentId }}</span>
                            </div>
                            <div>
                                <span class="font-medium text-gray-900 dark:text-gray-100">{{ __('Course') }}:</span>
                                <span>{{ $courseOptions[$courseCode] ?? $courseCode }}</span>
                            </div>
                        </div>
                        <flux:text class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                            {{ __('Contact an admin if you need to update these details.') }}
                        </flux:text>
                    </div>
                @endif
            @endif

            <div class="flex items-center gap-4">
                <div class="flex items-center justify-end">
                    <flux:button variant="primary" type="submit" class="w-full" data-test="update-profile-button">
                        {{ __('Save') }}
                    </flux:button>
                </div>

                <x-action-message class="me-3" on="profile-updated">
                    {{ __('Saved.') }}
                </x-action-message>
            </div>
        </form>

        <livewire:settings.delete-user-form />
    </x-settings.layout>
</section>
