<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;

new class extends Component {
    public string $theme = 'system';

    public function mount(): void
    {
        $this->theme = Auth::user()->theme_preference ?? 'system';
    }

    public function save(): void
    {
        $validated = $this->validate([
            'theme' => ['required', Rule::in(['light', 'dark', 'system'])],
        ]);

        $user = Auth::user();

        $user->forceFill([
            'theme_preference' => $validated['theme'],
        ])->save();

        // Update Flux appearance directly
        $this->dispatch('appearance-updated', theme: $validated['theme']);
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Appearance')" :subheading="__('Update the appearance settings for your account')">
        <flux:radio.group
            x-data
            variant="segmented"
            x-model="$flux.appearance"
            x-on:change="() => { $wire.theme = $flux.appearance; $wire.save(); }"
            data-test="appearance-radio-group"
        >
            <flux:radio value="light" icon="sun">{{ __('Light') }}</flux:radio>
            <flux:radio value="dark" icon="moon">{{ __('Dark') }}</flux:radio>
            <flux:radio value="system" icon="computer-desktop">{{ __('System') }}</flux:radio>
        </flux:radio.group>
    </x-settings.layout>
</section>

<script>
    // Listen for appearance updates from settings and sync to Flux
    window.addEventListener('appearance-updated', (event) => {
        if (window.Flux && event.detail?.theme) {
            Flux.appearance = event.detail.theme;
        }
    });
</script>
