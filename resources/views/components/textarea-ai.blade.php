@props(['name','label' => null,'buttonText' => 'Analyze with Gemini','analyzing' => false])

<label class="block text-sm font-medium text-gray-900">{{ $label ?? 'Log entry' }}</label>
<div class="mt-2 space-y-3">
    <textarea name="{{ $name }}" {{ $attributes->merge(['class' => 'w-full rounded-xl border-gray-200 bg-white text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 min-h-40']) }}></textarea>
    <x-button type="button" :disabled="$analyzing">
        <i data-lucide="sparkles" class="h-4 w-4"></i>
        <span>{{ $analyzing ? 'Analyzingâ€¦' : $buttonText }}</span>
    </x-button>
</div>
