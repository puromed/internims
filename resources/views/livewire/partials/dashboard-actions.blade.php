@php
$accentBg = [
    'rose' => 'bg-rose-50 text-rose-600',
    'gray' => 'bg-gray-100 text-gray-400',
];

$statusBg = [
    'yellow' => 'bg-yellow-50 text-yellow-700 ring-yellow-600/20',
    'green' => 'bg-green-50 text-green-700 ring-green-600/20',
    'gray' => 'bg-gray-100 text-gray-600 ring-gray-400/20',
];
@endphp

@foreach($actions as $action)
    <div class="group relative p-6 {{ $action['locked'] ? 'opacity-60 cursor-not-allowed' : 'hover:bg-gray-50 cursor-pointer' }}">
        <div>
            <span class="inline-flex rounded-lg {{ $accentBg[$action['accent']] ?? 'bg-gray-100 text-gray-400' }} p-3 ring-4 ring-white">
                <i data-lucide="{{ $action['icon'] }}" class="h-6 w-6"></i>
            </span>
        </div>
        <div class="mt-4">
            <h3 class="text-lg font-semibold {{ $action['locked'] ? 'text-gray-500' : 'text-gray-900' }}">
                <span class="absolute inset-0" aria-hidden="true"></span>
                {{ $action['title'] }}
                @if($action['locked'])
                    <span class="ml-2 inline-flex items-center rounded-full bg-gray-200 px-2 py-0.5 text-xs font-medium text-gray-600">
                        <i data-lucide="lock" class="mr-1 h-3 w-3"></i> Locked
                    </span>
                @endif
            </h3>
            <p class="mt-2 text-sm {{ $action['locked'] ? 'text-gray-400' : 'text-gray-500' }}">{{ $action['description'] }}</p>
        </div>
        <span class="pointer-events-none absolute top-6 right-6 text-gray-300 group-hover:text-gray-400">
            <i data-lucide="arrow-right" class="h-6 w-6"></i>
        </span>
        <div class="mt-4">
            <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium ring-1 ring-inset {{ $statusBg[$action['status_color']] ?? 'bg-gray-100 text-gray-700 ring-gray-400/20' }}">
                <i data-lucide="{{ $action['locked'] ? 'alert-triangle' : 'check' }}" class="mr-1 h-3 w-3"></i>
                {{ $action['status'] }}
            </span>
        </div>
    </div>
@endforeach
