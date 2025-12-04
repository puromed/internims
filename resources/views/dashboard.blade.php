<x-app-layout title="Dashboard" :nav="[
    ['label' => 'Dashboard', 'href'=>route('dashboard'), 'icon'=>'layout-dashboard', 'active'=>'dashboard'],
    ['label' => 'Eligibility', 'href'=>'#', 'icon'=>'shield-check'],
    ['label' => 'Placements', 'href'=>'#', 'icon'=>'briefcase-business'],
    ['label' => 'Logbooks', 'href'=>'#', 'icon'=>'notebook'],
    ['label' => 'Messages', 'href'=>'#', 'icon'=>'message-square'],
]">

    <div class="grid gap-6">
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <x-card title="Stage" subtitle="Current status">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-2xl font-semibold text-gray-900">Eligibility</p>
                        <p class="text-sm text-gray-500">Waiting for documents</p>
                    </div>
                    <x-badge variant="warning">Pending</x-badge>
                </div>
            </x-card>
            <x-card title="Supervisor" subtitle="Assigned">
                <p class="text-lg font-semibold text-gray-900">Not assigned</p>
                <p class="text-sm text-gray-500 mt-1">You’ll be matched after eligibility</p>
            </x-card>
            <x-card title="Next Logbook" subtitle="Due this week">
                <div class="flex items-center justify-between">
                    <p class="text-2xl font-semibold text-gray-900">Week 1</p>
                    <x-badge variant="info">Draft</x-badge>
                </div>
            </x-card>
            <x-card title="AI Analysis" subtitle="Last run">
                <p class="text-lg font-semibold text-gray-900">Not started</p>
                <p class="text-sm text-gray-500 mt-1">Run analysis after submitting logbook</p>
            </x-card>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <x-card class="lg:col-span-2" title="Action Items" subtitle="What to do next">
                <ul class="space-y-3 text-sm text-gray-700">
                    <li class="flex items-center gap-3">
                        <i data-lucide="upload-cloud" class="h-4 w-4 text-indigo-600"></i>
                        Upload eligibility documents (ID, enrollment, letter)
                        <x-badge class="ml-auto" variant="warning">Required</x-badge>
                    </li>
                    <li class="flex items-center gap-3">
                        <i data-lucide="notebook-pen" class="h-4 w-4 text-indigo-600"></i>
                        Draft your Week 1 logbook entry
                        <x-badge class="ml-auto" variant="info">Upcoming</x-badge>
                    </li>
                    <li class="flex items-center gap-3">
                        <i data-lucide="messages-square" class="h-4 w-4 text-indigo-600"></i>
                        Check messages for coordinator updates
                    </li>
                </ul>
            </x-card>

            <x-card title="Quick Start Logbook" subtitle="Draft and analyze">
                <div class="space-y-4">
                    <x-textarea-ai name="log_entry" label="Log entry" class="min-h-32" />
                    <x-button class="w-full">
                        <i data-lucide="send" class="h-4 w-4"></i>
                        Submit draft
                    </x-button>
                </div>
            </x-card>
        </div>

        <x-card title="Recent Logbooks" subtitle="Latest submissions">
            <div class="divide-y divide-gray-100">
                <div class="flex items-center justify-between py-3">
                    <div>
                        <p class="text-sm font-semibold text-gray-900">Week 0 — Orientation</p>
                        <p class="text-xs text-gray-500">Not submitted</p>
                    </div>
                    <x-badge variant="danger">Missing</x-badge>
                </div>
                <div class="flex items-center justify-between py-3">
                    <div>
                        <p class="text-sm font-semibold text-gray-900">Week 1 — First sprint</p>
                        <p class="text-xs text-gray-500">Draft in progress</p>
                    </div>
                    <x-badge variant="warning">Draft</x-badge>
                </div>
            </div>
        </x-card>
    </div>
</x-app-layout>