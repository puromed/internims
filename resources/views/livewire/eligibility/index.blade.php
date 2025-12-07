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
        <h2 class="text-3xl font-bold text-gray-900">Eligibility Verification</h2>
        <p class="mt-1 text-sm text-gray-500">Stage 1: Upload all required documents to unlock internship features.</p>
    </div>

    {{-- Progress --}}
    @php
        $approved = collect($requiredTypes)->filter(fn($t) => data_get($docs, "$t.status") === 'approved')->count();
        $total = count($requiredTypes);
        $pct = $total ? intval(($approved / $total) * 100) : 0;
    @endphp
    <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-200 p-4 flex items-center justify-between">
        <div class="w-full">
            <div class="flex items-center justify-between text-sm text-gray-600 mb-2">
                <span>Document Progress</span>
                <span class="font-semibold text-indigo-600">{{ $pct }}%</span>
            </div>
            <div class="h-2 rounded-full bg-gray-100 overflow-hidden">
                <div class="h-full bg-indigo-500" style="width: {{ $pct }}%"></div>
            </div>
        </div>
        <div class="ml-6 inline-flex items-center rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700 ring-1 ring-amber-200">
            {{ $approved }} of {{ $total }} Complete
        </div>
    </div>

    {{-- Cards --}}
    @php
        $statusMap = [
            'approved' => ['label' => 'Uploaded', 'class' => 'border-green-200 bg-green-50 text-green-700', 'icon' => 'check-circle-2'],
            'pending'  => ['label' => 'Submitted', 'class' => 'border-amber-200 bg-amber-50 text-amber-700', 'icon' => 'clock'],
            'rejected' => ['label' => 'Rejected', 'class' => 'border-rose-200 bg-rose-50 text-rose-700', 'icon' => 'alert-triangle'],
            'missing'  => ['label' => 'Not Submitted', 'class' => 'border-gray-200 bg-gray-50 text-gray-600', 'icon' => 'upload-cloud'],
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
            <div class="relative rounded-2xl border {{ $map['class'] }} bg-white/70 p-6 shadow-sm">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="flex items-center gap-2 text-sm font-semibold text-gray-900">
                            <i data-lucide="{{ $map['icon'] }}" class="h-4 w-4 {{ $map['class'] }}"></i>
                            {{ $label }}
                        </div>
                        <p class="mt-1 text-xs uppercase tracking-wide text-gray-500">Format: PDF • Max: 5MB</p>
                    </div>
                    <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset {{ $map['class'] }}">
                        {{ $map['label'] }}
                    </span>
                </div>

                <p class="mt-4 text-sm text-gray-600">
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

                <div class="mt-4 border-2 border-dashed border-gray-200 rounded-xl p-4 bg-white">
                    @if($doc && $doc['path'])
                        <div class="flex items-center justify-between text-sm text-gray-700">
                            <div class="flex items-center gap-2">
                                <i data-lucide="file" class="h-4 w-4 text-gray-500"></i>
                                <span class="font-medium">{{ basename($doc['path']) }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <a href="{{ Storage::disk('public')->url($doc['path']) }}" target="_blank" class="text-indigo-600 hover:text-indigo-700 text-sm">View</a>
                                <button wire:click="uploadDoc('{{ $type }}')" class="text-rose-600 hover:text-rose-700 text-sm">Replace</button>
                            </div>
                        </div>
                    @endif
                    <div class="mt-3 flex items-center gap-3">
                        <input type="file" wire:model="uploads.{{ $type }}" class="text-sm text-gray-600">
                        <button wire:click="uploadDoc('{{ $type }}')" class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                            {{ $doc ? 'Replace' : 'Click to Upload' }}
                        </button>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Guidelines --}}
    <div class="rounded-2xl bg-indigo-50 border border-indigo-100 p-6 shadow-sm">
        <div class="flex items-center gap-2 text-indigo-800 font-semibold mb-3">
            <i data-lucide="info" class="h-5 w-5"></i>
            Document Guidelines
        </div>
        <ul class="space-y-2 text-sm text-indigo-900/90">
            <li class="flex items-start gap-2"><i data-lucide="check" class="h-4 w-4 mt-0.5"></i> All documents must be in PDF format</li>
            <li class="flex items-start gap-2"><i data-lucide="check" class="h-4 w-4 mt-0.5"></i> Maximum file size is 5MB per document</li>
            <li class="flex items-start gap-2"><i data-lucide="check" class="h-4 w-4 mt-0.5"></i> Transcript must be an official copy</li>
            <li class="flex items-start gap-2"><i data-lucide="check" class="h-4 w-4 mt-0.5"></i> Processing takes 2–3 business days after all documents are uploaded</li>
        </ul>
    </div>
</div>
