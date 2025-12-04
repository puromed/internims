<x-app-layout title="Component Gallery" :nav="[['label'=>'Gallery','href'=>route('gallery'),'icon'=>'palette','active'=>'gallery']]">
    <div class="grid gap-6 lg:grid-cols-2">
        <x-card title="Badges" subtitle="Variants">
            <div class="flex flex-wrap gap-2">
                <x-badge>Default</x-badge>
                <x-badge variant="success">Success</x-badge>
                <x-badge variant="warning">Warning</x-badge>
                <x-badge variant="danger">Danger</x-badge>
                <x-badge variant="info">Info</x-badge>
            </div>
        </x-card>
        <x-card title="Buttons" subtitle="Variants">
            <div class="flex flex-wrap gap-3">
                <x-button><i data-lucide="play" class="h-4 w-4"></i>Primary</x-button>
                <x-button variant="secondary">Secondary</x-button>
                <x-button variant="ghost">Ghost</x-button>
            </div>
        </x-card>
        <x-card title="File Upload">
            <x-file-upload name="resume" label="Resume" hint="PDF up to 5MB"/>
        </x-card>
        <x-card title="Textarea AI">
            <x-textarea-ai name="log" label="Log entry"/>
        </x-card>
    </div>
</x-app-layout>
