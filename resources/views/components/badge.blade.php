@props(['variant' => 'default'])

@php
$styles = [
     'default' => 'bg-gray-100 text-gray-800 ring-gray-200',
    'success' => 'bg-emerald-100 text-emerald-800 ring-emerald-200',
    'warning' => 'bg-amber-100 text-amber-800 ring-amber-200',
    'danger'  => 'bg-rose-100 text-rose-800 ring-rose-200',
    'info'    => 'bg-indigo-100 text-indigo-800 ring-indigo-200',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-semibold ring-1 ring-inset '.$styles[$variant]]) }}>
    {{ $slot }}
</span>