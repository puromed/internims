<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    @include('partials.head')
</head>

<body class="min-h-screen bg-white antialiased dark:bg-linear-to-b dark:from-neutral-950 dark:to-neutral-900">
    <div class="bg-background flex min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10">
        <div class="flex w-full max-w-sm flex-col gap-2">
            <a href="{{ route('home') }}" class="flex flex-col items-center gap-2 font-medium" wire:navigate>
                <div class="flex h-12 w-full items-center justify-center mb-1">
                    <img src="{{ asset('images/logo.png') }}" class="h-full w-auto object-contain dark:hidden"
                        alt="UiTM Logo">
                    <img src="{{ asset('images/logo-dark.png') }}"
                        class="h-full w-auto object-contain hidden dark:block" alt="UiTM Logo Dark">
                </div>
                <span class="sr-only">InternIMS Portal</span>
            </a>
            <div class="flex flex-col gap-6">
                {{ $slot }}
            </div>
        </div>
    </div>

    {{-- Floating Help Button --}}
    <div x-data="{
        routeName: @js(Route::currentRouteName()),
        visible: false,
        isLoginPage: false,
        init() {
            this.checkVisibility();
            document.addEventListener('livewire:navigated', () => {
                this.$nextTick(() => {
                    const meta = document.querySelector('meta[name=route-name]');
                    if (meta) { this.routeName = meta.getAttribute('content'); }
                    this.checkVisibility();
                });
            });
        },
        checkVisibility() {
            this.visible = window.internimsTour?.hasTour(this.routeName) ?? false;
            this.isLoginPage = this.routeName === 'login';
        },
        startTour() { window.internimsTour?.startTour(this.routeName); },
    }" x-show="visible" x-transition x-cloak>
        <button @click="startTour()" :class="isLoginPage ? 'tour-help-btn tour-help-btn-glow' : 'tour-help-btn'" title="Take a tour of this page">
            <i data-lucide="help-circle" class="size-5"></i>
        </button>
    </div>

    @fluxScripts
</body>

</html>