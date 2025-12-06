{{-- Important Dates Card --}}
<div class="mt-6 overflow-hidden rounded-2xl bg-gradient-to-br from-indigo-500 to-indigo-900 shadow-sm p-6 text-white">
    <div class="flex items-center gap-3 mb-3">
        <div class="p-2 bg-white/20 rounded-lg">
            <i data-lucide="calendar" class="h-5 w-5"></i>
        </div>
        <h4 class="font-semibold">Important Dates</h4>
    </div>
    <ul class="space-y-2 text-sm text-white/90">
        <li class="flex justify-between">
            <span>Document Deadline</span>
            <span class="font-medium text-white">{{ $dates['deadline'] ?? '—' }}</span>
        </li>
        <li class="flex justify-between">
            <span>Placement Start</span>
            <span class="font-medium text-white">{{ $dates['placement_start'] ?? '—' }}</span>
        </li>
        <li class="flex justify-between">
            <span>Internship Ends</span>
            <span class="font-medium text-white">{{ $dates['internship_end'] ?? '—' }}</span>
        </li>
    </ul>
</div>
