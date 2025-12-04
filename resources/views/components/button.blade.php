@props(['variant' => 'primary', 'as' => 'button'])

@php
$base = 'inline-flex items-center justify-center gap-2 rounded-lg text-sm font-semibold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2';
$variants = [
    'primary' => 'bg-indigo-600 text-white hover:bg-indigo-700 focus-visible:ring-indigo-500 px-4 py-2',
    'secondary' => 'bg-white text-gray-900 border border-gray-200 hover:border-gray-300 focus-visible:ring-gray-300 px-4 py-2',
    'ghost' => 'bg-transparent text-gray-700 hover:bg-gray-100 px-3 py-2',
];
@endphp

<{{ $as }} {{ $attributes->merge(['class' => $base.' '.$variants[$variant]]) }}>
    {{ $slot }}
</{{ $as }}>
