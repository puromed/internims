<?php

use App\Models\EligibilityDoc;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    public array $requiredTypes = ['resume', 'transcript', 'offer_letter'];
    public array $docs = [];
    public array $uploads = [];

    public function mount(): void
    {
        $this->loadDocs();
    }

    public function uploadDoc(string $type): void
    {
        $this->validate([
            "uploads.$type" => 'required|file|max:5120',
        ]);

        $file = $this->uploads[$type];
        $path = $file->store("eligibility/{$type}", 'public');

        EligibilityDoc::updateOrCreate(
            ['user_id' => Auth::id(), 'type' => $type],
            ['path' => $path, 'status' => 'pending']
        );

        $this->reset("uploads.$type");
        $this->loadDocs();
        $this->dispatch('notify', message: 'Document uploaded.');
    }

    protected function loadDocs(): void
    {
        $this->docs = Auth::user()
            ->eligibilityDocs()
            ->get()
            ->keyBy('type')
            ->toArray();
    }
}; ?>

<div class="space-y-8">
    {{-- Header --}}
    <div>
        <flux:heading size="xl">Eligibility Verification</flux:heading>
        <flux:subheading>Stage 1: Upload all required documents to unlock internship features.</flux:subheading>
    </div>

    {{-- Progress --}}
    @php
        $approved = collect($requiredTypes)->filter(fn($t) => data_get($docs, "$t.status") === 'approved')->count();
        $total = count($requiredTypes);
        $pct = $total ? intval(($approved / $total) * 100) : 0;
    @endphp
    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-zinc-900">
        <div class="flex items-center justify-between text-sm text-gray-600 dark:text-gray-400 mb-2">
            <span>Document Progress</span>
            <span class="font-semibold text-indigo-600 dark:text-indigo-400">{{ $pct }}%</span>
        </div>
        <div class="h-2 rounded-full bg-gray-100 dark:bg-zinc-800 overflow-hidden">
            <div class="h-full bg-indigo-600 dark:bg-indigo-500 transition-all duration-500" style="width: {{ $pct }}%"></div>
        </div>
        <div class="mt-4 flex justify-end">
            <div class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700 ring-1 ring-amber-200 dark:bg-amber-900/30 dark:text-amber-300 dark:ring-amber-800/50">
                <span>{{ $approved }} of {{ $total }} Approved</span>
            </div>
        </div>
    </div>

    {{-- Cards --}}
    @php
        $statusMap = [
            'approved' => ['label' => 'Uploaded', 'color' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300', 'icon' => 'check-circle'],
            'pending'  => ['label' => 'Submitted', 'color' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300', 'icon' => 'clock'],
            'rejected' => ['label' => 'Rejected', 'color' => 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300', 'icon' => 'exclamation-circle'],
            'missing'  => ['label' => 'Not Submitted', 'color' => 'bg-gray-100 text-gray-600 dark:bg-zinc-800 dark:text-gray-400', 'icon' => 'cloud-arrow-up'],
        ];
    @endphp
    <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
        @foreach($requiredTypes as $type)
            @php
                $doc = $docs[$type] ?? null;
                $status = $doc['status'] ?? 'missing';
                $map = $statusMap[$status];
                $label = \Illuminate\Support\Str::of($type)->replace('_',' ')->title();
            @endphp
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-zinc-900">
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <flux:heading size="lg">{{ $label }}</flux:heading>
                        <flux:subheading class="text-xs uppercase tracking-wide">Format: PDF • Max: 5MB</flux:subheading>
                    </div>
                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold {{ $map['color'] }}">
                        <flux:icon name="{{ $map['icon'] }}" class="size-3.5" />
                        {{ $map['label'] }}
                    </span>
                </div>

                <p class="mb-6 text-sm text-gray-600 dark:text-gray-400">
                    @if($status === 'missing')
                        Your {{ strtolower($label) }} is required to complete Stage 1.
                    @elseif($status === 'pending')
                        Submitted and awaiting review.
                    @elseif($status === 'rejected')
                        Please upload a corrected document.
                    @else
                        Approved {{ \Illuminate\Support\Carbon::parse($doc['reviewed_at'] ?? $doc['updated_at'] ?? now())->diffForHumans() }}
                    @endif
                </p>

                @if($doc && $doc['path'])
                    <div class="mb-4 rounded-lg border border-gray-200 bg-gray-50 p-3 flex items-center justify-between dark:border-zinc-700 dark:bg-zinc-800/50">
                        <div class="flex items-center gap-2 overflow-hidden">
                            <flux:icon name="document" class="size-4 text-gray-400" />
                            <span class="truncate text-sm font-medium text-gray-700 dark:text-gray-300">{{ basename($doc['path']) }}</span>
                        </div>
                        <a href="{{ Storage::disk('public')->url($doc['path']) }}" target="_blank" class="text-sm font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">View</a>
                    </div>
                @endif

                <div class="flex flex-col gap-3">
                    <input type="file" wire:model="uploads.{{ $type }}" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:text-gray-400 dark:file:bg-indigo-900/30 dark:file:text-indigo-400 cursor-pointer"/>
                    
                    <flux:button wire:click="uploadDoc('{{ $type }}')" class="w-full" :disabled="!isset($uploads[$type])">
                        {{ $doc ? 'Replace Document' : 'Upload Document' }}
                    </flux:button>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Guidelines --}}
    <div class="rounded-xl border border-indigo-100 bg-indigo-50 p-6 shadow-sm dark:border-indigo-900/50 dark:bg-indigo-950/20">
        <div class="flex items-center gap-2 text-indigo-800 dark:text-indigo-300 font-semibold mb-3">
            <flux:icon name="information-circle" class="size-5" />
            Document Guidelines
        </div>
        <ul class="space-y-2 text-sm text-indigo-900/90 dark:text-indigo-200/80">
            <li class="flex items-start gap-2"><flux:icon name="check" class="size-4 mt-0.5" /> All documents must be in PDF format</li>
            <li class="flex items-start gap-2"><flux:icon name="check" class="size-4 mt-0.5" /> Maximum file size is 5MB per document</li>
            <li class="flex items-start gap-2"><flux:icon name="check" class="size-4 mt-0.5" /> Transcript must be an official copy</li>
            <li class="flex items-start gap-2"><flux:icon name="check" class="size-4 mt-0.5" /> Processing takes 2–3 business days after all documents are uploaded</li>
        </ul>
    </div>
</div>
