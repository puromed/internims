@props(['name','label' => null,'hint' => null])

<label class="block text-sm font-medium text-gray-900">{{ $label ?? 'Upload file' }}</label>
<div class="mt-2">
    <input type="file" name="{{ $name }}" {{ $attributes->merge(['class' => 'block w-full text-sm text-gray-700 file:me-4 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-indigo-700 hover:file:bg-indigo-100']) }}>
    @if($hint)<p class="mt-1 text-xs text-gray-500">{{ $hint }}</p>@endif
</div>
