{{-- Important Dates Card --}}
<div class="mt-6">
    <div class="rounded-xl bg-gradient-to-br from-indigo-500 to-indigo-900 dark:from-indigo-600 dark:to-indigo-950 p-6 shadow-md">
        <div class="flex items-center gap-3 mb-4">
            <div class="p-2 bg-white/20 rounded-lg backdrop-blur-sm">
                <flux:icon name="calendar" class="size-5 text-white" />
            </div>
            <flux:heading class="!text-white">Important Dates</flux:heading>
        </div>

        @if(count($dates) > 0)
            <ul class="space-y-3 text-sm text-white/90">
                @foreach($dates as $date)
                    <li class="flex justify-between items-center border-b border-white/10 pb-2 last:border-0 last:pb-0">
                        <span>{{ $date['title'] }}</span>
                        <span class="font-medium text-white font-mono">{{ $date['date'] }}</span>
                    </li>
                @endforeach
            </ul>
        @else
            <div class="flex flex-col items-center justify-center py-4 text-center">
                <flux:icon name="calendar" class="size-8 text-white/40 mb-2" />
                <p class="text-sm text-white/70">No important dates set for this semester.</p>
            </div>
        @endif
    </div>
</div>
