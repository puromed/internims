<?php

use App\Models\AcademicSetting;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;

new class extends Component {
    public string $name = '';
    public string $email = '';
    public string $studentId = '';
    public string $programCode = '';
    public string $currentSemesterCode = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $user = Auth::user();

        $this->name = $user->name;
        $this->email = $user->email;
        $this->studentId = (string) ($user->student_id ?? '');
        $this->programCode = (string) ($user->program_code ?? '');
        $this->currentSemesterCode = AcademicSetting::currentSemesterCode();
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

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

        if ($user->role === 'student') {
            $rules['studentId'] = [
                'required',
                'digits:10',
                Rule::unique(User::class, 'student_id')->ignore($user->id),
            ];
            $rules['programCode'] = ['required', 'string', 'max:50'];
        }

        $validated = $this->validate($rules);

        $attributes = [
            'name' => $validated['name'],
            'email' => $validated['email'],
        ];

        if ($user->role === 'student') {
            $attributes['student_id'] = $validated['studentId'];
            $attributes['program_code'] = mb_strtoupper($validated['programCode']);
        }

        $user->fill($attributes);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

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

    <x-settings.layout :heading="__('Profile')" :subheading="__('Update your personal and academic details')">
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

            @if(auth()->user()?->role === 'student')
                <div class="grid gap-6 md:grid-cols-2">
                    <flux:input
                        wire:model="studentId"
                        label="Student ID"
                        type="text"
                        required
                        autocomplete="off"
                        placeholder="e.g. 2024123456"
                    />

                    <flux:input
                        wire:model="programCode"
                        label="Program Code"
                        type="text"
                        required
                        autocomplete="off"
                        placeholder="e.g. CS110"
                    />
                </div>

                <flux:input
                    wire:model="currentSemesterCode"
                    label="Current Semester"
                    type="text"
                    readonly
                    disabled
                />
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
