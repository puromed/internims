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
    <div class="group relative p-6 {{ $action['locked'] ? 'opacity-60 cursor-not-allowed' : 'hover:bg-gray-50 dark:hover:bg-zinc-800/50 cursor-pointer' }}">
        @if(!$action['locked'] && isset($action['url']))
            <a href="{{ $action['url'] }}" class="absolute inset-0 z-10" aria-label="{{ $action['title'] }}" wire:navigate></a>
        @endif
        <div>
            <span class="inline-flex rounded-lg {{ $accentBg[$action['accent']] ?? 'bg-gray-100 text-gray-400 dark:bg-zinc-800 dark:text-zinc-500' }} p-3 ring-4 ring-white dark:ring-zinc-900">
                <flux:icon name="{{ $action['icon'] }}" class="size-6" />
            </span>
        </div>
        <div class="mt-4">
            <h3 class="text-lg font-semibold {{ $action['locked'] ? 'text-gray-500 dark:text-gray-500' : 'text-gray-900 dark:text-white' }}">
                {{ $action['title'] }}
                @if($action['locked'])
                    <span class="ml-2 inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600 dark:bg-zinc-800 dark:text-zinc-400">
                        <flux:icon name="lock-closed" class="mr-1 size-3" /> Locked
                    </span>
                @endif
            </h3>
            <p class="mt-2 text-sm {{ $action['locked'] ? 'text-gray-400 dark:text-gray-600' : 'text-gray-500 dark:text-gray-400' }}">{{ $action['description'] }}</p>
        </div>
        <span class="pointer-events-none absolute top-6 right-6 text-gray-300 dark:text-gray-600 group-hover:text-gray-400 dark:group-hover:text-gray-500">
            <flux:icon name="arrow-right" class="size-6" />
        </span>
        <div class="mt-4">
            @php
                $statusColor = match($action['status_color']) {
                    'yellow' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300 ring-amber-600/20',
                    'green'  => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300 ring-emerald-600/20',
                    default  => 'bg-gray-100 text-gray-800 dark:bg-zinc-800 dark:text-zinc-400 ring-gray-400/20',
                };
            @endphp
            <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium ring-1 ring-inset {{ $statusColor }}">
                <flux:icon name="{{ $action['locked'] ? 'exclamation-triangle' : 'check' }}" class="mr-1 size-3" />
                {{ $action['status'] }}
            </span>
        </div>
    </div>
@endforeach
