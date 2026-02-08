<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    @include('partials.head')
</head>

<body class="min-h-screen bg-white dark:bg-zinc-800">
    <flux:sidebar sticky stashable class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

        <a href="{{ auth()->user()->isAdmin() ? route('admin.dashboard') : (auth()->user()->isFaculty() ? route('faculty.dashboard') : route('dashboard')) }}"
            class="me-5 flex items-center gap-2 w-full justify-center py-2" wire:navigate>
            <div class="flex items-center gap-2">
                <x-app-logo />
            </div>
        </a>

        <flux:navlist variant="outline">
            @if(auth()->user()->isAdmin())
                {{-- Admin Navigation --}}
                <flux:navlist.group :heading="__('Admin')" class="grid">
                    <flux:navlist.item :href="route('admin.dashboard')" :current="request()->routeIs('admin.dashboard')"
                        wire:navigate>
                        <x-slot:icon><i data-lucide="layout-dashboard" class="size-5"></i></x-slot:icon>
                        {{ __('Dashboard') }}
                    </flux:navlist.item>
                    <flux:navlist.item :href="route('admin.eligibility.index')"
                        :current="request()->routeIs('admin.eligibility.*')" wire:navigate>
                        <x-slot:icon><i data-lucide="file-check" class="size-5"></i></x-slot:icon>
                        {{ __('Eligibility Review') }}
                    </flux:navlist.item>
                    <flux:navlist.item :href="route('admin.companies.index')"
                        :current="request()->routeIs('admin.companies.*')" wire:navigate>
                        <x-slot:icon><i data-lucide="building-2" class="size-5"></i></x-slot:icon>
                        {{ __('Company Proposals') }}
                    </flux:navlist.item>
                    <flux:navlist.item :href="route('admin.users.index')" :current="request()->routeIs('admin.users.*')"
                        wire:navigate>
                        <x-slot:icon><i data-lucide="users" class="size-5"></i></x-slot:icon>
                        {{ __('User Management') }}
                    </flux:navlist.item>
                    <flux:navlist.item :href="route('admin.assignments.index')"
                        :current="request()->routeIs('admin.assignments.*')" wire:navigate>
                        <x-slot:icon><i data-lucide="user-plus" class="size-5"></i></x-slot:icon>
                        {{ __('Faculty Assignments') }}
                    </flux:navlist.item>
                    <flux:navlist.item :href="route('admin.dates.index')" :current="request()->routeIs('admin.dates.*')"
                        wire:navigate>
                        <x-slot:icon><i data-lucide="calendar" class="size-5"></i></x-slot:icon>
                        {{ __('Important Dates') }}
                    </flux:navlist.item>
                </flux:navlist.group>

                {{-- Admin can also access Faculty features --}}
                <flux:navlist.group :heading="__('Faculty Tools')" class="grid">
                    <flux:navlist.item :href="route('faculty.dashboard')" :current="request()->routeIs('faculty.dashboard')"
                        wire:navigate>
                        <x-slot:icon><i data-lucide="briefcase" class="size-5"></i></x-slot:icon>
                        {{ __('Faculty Dashboard') }}
                    </flux:navlist.item>
                    <flux:navlist.item :href="route('faculty.logbooks.index')"
                        :current="request()->routeIs('faculty.logbooks.*')" wire:navigate>
                        <x-slot:icon><i data-lucide="book-open" class="size-5"></i></x-slot:icon>
                        {{ __('Student Logbooks') }}
                    </flux:navlist.item>
                </flux:navlist.group>
            @elseif(auth()->user()->isFaculty())
                {{-- Faculty Navigation --}}
                <flux:navlist.group :heading="__('Faculty')" class="grid">
                    <flux:navlist.item :href="route('faculty.dashboard')" :current="request()->routeIs('faculty.dashboard')"
                        wire:navigate>
                        <x-slot:icon><i data-lucide="layout-dashboard" class="size-5"></i></x-slot:icon>
                        {{ __('Dashboard') }}
                    </flux:navlist.item>
                    <flux:navlist.item :href="route('faculty.logbooks.index')"
                        :current="request()->routeIs('faculty.logbooks.*')" wire:navigate>
                        <x-slot:icon><i data-lucide="book-open" class="size-5"></i></x-slot:icon>
                        {{ __('Student Logbooks') }}
                    </flux:navlist.item>
                </flux:navlist.group>
            @else
                {{-- Student Navigation --}}
                @php
                    $user = auth()->user();
                    $requiredDocTypes = ['resume', 'transcript', 'offer_letter'];
                    $eligibilityDocs = $user->eligibilityDocs()->get()->keyBy('type');
                    $allDocsApproved = collect($requiredDocTypes)->every(fn($type) => ($eligibilityDocs[$type]->status ?? '') === 'approved');
                    $hasInternship = $user->internships()->exists();

                    $placementLocked = !$allDocsApproved;
                    $logbookLocked = !$hasInternship;
                @endphp
                <flux:navlist.group :heading="__('Internship')" class="grid">
                    <flux:navlist.item :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                        <x-slot:icon><i data-lucide="layout-dashboard" class="size-5"></i></x-slot:icon>
                        {{ __('Dashboard') }}
                    </flux:navlist.item>
                    <flux:navlist.item :href="route('eligibility.index')" :current="request()->routeIs('eligibility.index')"
                        wire:navigate>
                        <x-slot:icon><i data-lucide="file-check" class="size-5"></i></x-slot:icon>
                        {{ __('Eligibility Docs') }}
                    </flux:navlist.item>

                    @if($placementLocked)
                        <div
                            class="flex items-center justify-between px-3 py-2 text-sm text-gray-400 dark:text-gray-500 cursor-not-allowed opacity-60">
                            <div class="flex items-center gap-2">
                                <i data-lucide="briefcase" class="size-5"></i>
                                <span>{{ __('My Placement') }}</span>
                            </div>
                            <span
                                class="inline-flex items-center gap-1 rounded-md bg-gray-100 dark:bg-zinc-800 px-1.5 py-0.5 text-xs font-medium text-gray-500 dark:text-gray-400">
                                <i data-lucide="lock" class="size-3"></i>
                            </span>
                        </div>
                    @else
                        <flux:navlist.item :href="route('placement.index')" :current="request()->routeIs('placement.index')"
                            wire:navigate>
                            <x-slot:icon><i data-lucide="briefcase" class="size-5"></i></x-slot:icon>
                            {{ __('My Placement') }}
                        </flux:navlist.item>
                    @endif

                    @if($logbookLocked)
                        <div
                            class="flex items-center justify-between px-3 py-2 text-sm text-gray-400 dark:text-gray-500 cursor-not-allowed opacity-60">
                            <div class="flex items-center gap-2">
                                <i data-lucide="book-open" class="size-5"></i>
                                <span>{{ __('Weekly Logbooks') }}</span>
                            </div>
                            <span
                                class="inline-flex items-center gap-1 rounded-md bg-gray-100 dark:bg-zinc-800 px-1.5 py-0.5 text-xs font-medium text-gray-500 dark:text-gray-400">
                                <i data-lucide="lock" class="size-3"></i>
                            </span>
                        </div>
                    @else
                        <flux:navlist.item :href="route('logbooks.index')" :current="request()->routeIs('logbooks.index')"
                            wire:navigate>
                            <x-slot:icon><i data-lucide="book-open" class="size-5"></i></x-slot:icon>
                            {{ __('Weekly Logbooks') }}
                        </flux:navlist.item>
                    @endif
                </flux:navlist.group>
            @endif
        </flux:navlist>



        <flux:spacer />

        <div class="hidden lg:flex items-center justify-between px-2 mb-4 gap-2">
            <livewire:notifications.bell />

            <flux:dropdown x-data align="end">
                <flux:button variant="subtle" square class="group" aria-label="Preferred color scheme">
                    <flux:icon.sun x-show="$flux.appearance === 'light'" variant="mini"
                        class="text-zinc-600 dark:text-white" />
                    <flux:icon.moon x-show="$flux.appearance === 'dark'" variant="mini"
                        class="text-zinc-600 dark:text-white" />
                    <flux:icon.moon x-show="$flux.appearance === 'system' && $flux.dark" variant="mini"
                        class="text-zinc-600 dark:text-white" />
                    <flux:icon.sun x-show="$flux.appearance === 'system' && ! $flux.dark" variant="mini"
                        class="text-zinc-600 dark:text-white" />
                </flux:button>

                <flux:menu>
                    <flux:menu.item icon="sun" x-on:click="$flux.appearance = 'light'">{{ __('Light') }}
                    </flux:menu.item>
                    <flux:menu.item icon="moon" x-on:click="$flux.appearance = 'dark'">{{ __('Dark') }}</flux:menu.item>
                    <flux:menu.item icon="computer-desktop" x-on:click="$flux.appearance = 'system'">{{ __('System') }}
                    </flux:menu.item>
                </flux:menu>
            </flux:dropdown>
        </div>

        <!-- Desktop User Menu -->
        <flux:dropdown class="hidden lg:block" position="bottom" align="start">
            <flux:profile :name="auth()->user()->name" :initials="auth()->user()->initials()"
                icon:trailing="chevrons-up-down" data-test="sidebar-menu-button" />

            <flux:menu class="w-[220px]">
                <flux:menu.radio.group>
                    <div class="p-0 text-sm font-normal">
                        <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                            <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                <span
                                    class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white">
                                    {{ auth()->user()->initials() }}
                                </span>
                            </span>

                            <div class="grid flex-1 text-start text-sm leading-tight">
                                <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                            </div>
                        </div>
                    </div>
                </flux:menu.radio.group>

                <flux:menu.separator />

                <flux:menu.radio.group>
                    <flux:menu.item :href="route('profile.edit')" wire:navigate>
                        <x-slot:icon><i data-lucide="settings" class="size-4"></i></x-slot:icon>
                        {{ __('Settings') }}
                    </flux:menu.item>
                </flux:menu.radio.group>

                <flux:menu.separator />

                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <flux:menu.item as="button" type="submit" class="w-full" data-test="logout-button">
                        <x-slot:icon><i data-lucide="log-out" class="size-4"></i></x-slot:icon>
                        {{ __('Log Out') }}
                    </flux:menu.item>
                </form>
            </flux:menu>
        </flux:dropdown>
    </flux:sidebar>

    <!-- Mobile User Menu -->
    <flux:header class="lg:hidden">
        <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

        <flux:spacer />

        <livewire:notifications.bell />

        <flux:dropdown position="top" align="end">
            <flux:profile :initials="auth()->user()->initials()" icon-trailing="chevron-down" />

            <flux:menu>
                <flux:menu.radio.group>
                    <div class="p-0 text-sm font-normal">
                        <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                            <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                <span
                                    class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white">
                                    {{ auth()->user()->initials() }}
                                </span>
                            </span>

                            <div class="grid flex-1 text-start text-sm leading-tight">
                                <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                            </div>
                        </div>
                    </div>
                </flux:menu.radio.group>

                <flux:menu.separator />

                <flux:menu.radio.group>
                    <flux:menu.item :href="route('profile.edit')" wire:navigate>
                        <x-slot:icon><i data-lucide="settings" class="size-4"></i></x-slot:icon>
                        {{ __('Settings') }}
                    </flux:menu.item>
                </flux:menu.radio.group>

                <flux:menu.separator />

                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <flux:menu.item as="button" type="submit" class="w-full" data-test="logout-button">
                        <x-slot:icon><i data-lucide="log-out" class="size-4"></i></x-slot:icon>
                        {{ __('Log Out') }}
                    </flux:menu.item>
                </form>
            </flux:menu>
        </flux:dropdown>
    </flux:header>

    {{ $slot }}

    {{-- Floating Help Tour Button --}}
    <div
        x-data="{
            routeName: @js(Route::currentRouteName()),
            visible: false,
            init() {
                this.checkVisibility();
                document.addEventListener('livewire:navigated', () => {
                    this.$nextTick(() => {
                        const meta = document.querySelector('meta[name=route-name]');
                        if (meta) {
                            this.routeName = meta.getAttribute('content');
                        }
                        this.checkVisibility();
                    });
                });
            },
            checkVisibility() {
                this.visible = window.internimsTour?.hasTour(this.routeName) ?? false;
            },
            startTour() {
                window.internimsTour?.startTour(this.routeName);
            },
        }"
        x-show="visible"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-90"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-90"
        x-cloak
    >
        <button
            @click="startTour()"
            class="tour-help-btn"
            title="Take a tour of this page"
            aria-label="Page tour help"
        >
            <i data-lucide="help-circle" class="size-5"></i>
        </button>
    </div>

    @fluxScripts
    <script>
        (() => {
            // Sync Flux appearance with backend
            const updateUrl = @json(route('appearance.update'));
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

            // Initialize Flux appearance from server preference
            const serverPreference = @json(auth()->user()->theme_preference ?? 'system');
            if (!localStorage.getItem('flux:appearance')) {
                Flux.appearance = serverPreference;
            }

            // Watch for appearance changes and sync to backend
            const observer = new MutationObserver(() => {
                const currentAppearance = Flux.appearance;

                fetch(updateUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ theme: currentAppearance }),
                }).catch(() => { });
            });

            // Observe data-flux-appearance attribute changes
            observer.observe(document.documentElement, {
                attributes: true,
                attributeFilter: ['data-flux-appearance']
            });

            // Also listen for direct property changes
            let lastAppearance = Flux.appearance;
            setInterval(() => {
                if (Flux.appearance !== lastAppearance) {
                    lastAppearance = Flux.appearance;

                    fetch(updateUrl, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrf,
                            Accept: 'application/json',
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ theme: lastAppearance }),
                    }).catch(() => { });
                }
            }, 500);
        })();

        const renderLucide = () => window.lucide?.createIcons({ icons: window.lucide?.icons });

        document.addEventListener('DOMContentLoaded', renderLucide);
        document.addEventListener('livewire:navigated', renderLucide);

    </script>
</body>

</html>