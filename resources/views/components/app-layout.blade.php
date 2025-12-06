@props(['title' => null, 'nav' => [], 'flash' => session('status')])

<x-layouts.app.sidebar>
    @if($flash)
        <div class="px-4 sm:px-6 lg:px-8 py-4">
            <div class="rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800 flex items-center gap-2">
                <i data-lucide="check-circle-2" class="h-4 w-4"></i>
                <span>{{ $flash }}</span>
            </div>
        </div>
    @endif

    <main class="py-10">
        <div class="px-4 sm:px-6 lg:px-8">
            {{ $slot }}
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', () => window.lucide?.createIcons({ icons: window.lucide?.icons }));
        document.addEventListener('livewire:navigated', () => window.lucide?.createIcons({ icons: window.lucide?.icons }));
    </script>
</x-layouts.app.sidebar>
