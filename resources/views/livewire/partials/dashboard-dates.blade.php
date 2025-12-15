{{-- Important Dates Card --}}
<div class="mt-6 overflow-hidden rounded-2xl bg-gradient-to-br from-indigo-500 to-indigo-900 shadow-sm p-6 text-white h-auto dark:from-indigo-600 dark:to-indigo-950">
    <div class="flex items-center gap-3 mb-4">
        <div class="p-2 bg-white/20 rounded-lg backdrop-blur-sm">
            <flux:icon name="calendar" class="size-5 text-white" />
        </div>
        <h4 class="font-semibold tracking-wide">Important Dates</h4>
    </div>
    <ul class="space-y-3 text-sm text-white/90">
        <li class="flex justify-between items-center border-b border-white/10 pb-2 last:border-0 last:pb-0">
            <span>Document Deadline</span>
            <span class="font-medium text-white font-mono">{{ $dates['deadline'] ?? '—' }}</span>
        </li>
        <li class="flex justify-between items-center border-b border-white/10 pb-2 last:border-0 last:pb-0">
            <span>Placement Start</span>
            <span class="font-medium text-white font-mono">{{ $dates['placement_start'] ?? '—' }}</span>
        </li>
        <li class="flex justify-between items-center border-b border-white/10 pb-2 last:border-0 last:pb-0">
            <span>Internship Ends</span>
            <span class="font-medium text-white font-mono">{{ $dates['internship_end'] ?? '—' }}</span>
        </li>
    </ul>
</div>
