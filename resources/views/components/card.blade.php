@props(['title' => null, 'subtitle' => null, 'actions' => null])

<div {{ $attributes->merge(['class' => 'bg-white border border-gray-200 rounded-2xl shadow-sm p-6']) }}>
    @if($title || $actions)
        <div class="flex items-start justify-between gap-3 mb-4">
            <div>
                @if($title)<h3 class="text-sm font-semibold text-gray-900">{{ $title }}</h3>@endif
                @if($subtitle)<p class="text-sm text-gray-500">{{ $subtitle }}</p>@endif
            </div>
            @if($actions)<div class="flex items-center gap-2">{{ $actions }}</div>@endif
        </div>
    @endif
    {{ $slot }}
</div>
