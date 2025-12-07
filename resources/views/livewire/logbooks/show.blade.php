<?php

use App\Models\LogbookEntry;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Component;

new class extends Component {
    public LogbookEntry $logbook;

    public function mount(LogbookEntry $logbook): void
    {
        abort_unless(
            $logbook->user_id === Auth::id(),
            403
        );

        $this->logbook = $logbook->loadMissing('user');
    }
}; ?>

<div class="space-y-6">
    {{-- Header Section with Week Number and Status Badge --}}
    <div class="md:flex md:items-center md:justify-between">
        <div class="min-w-0 flex-1">
            <div class="flex items-center gap-3 mb-2">
                <a href="{{ route('logbooks.index') }}" class="inline-flex items-center gap-1 text-sm text-indigo-600 hover:text-indigo-700">
                    <i data-lucide="arrow-left" class="h-4 w-4"></i>
                    Back to Logbooks
                </a>
            </div>
            <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight">
                Week {{ $logbook->week_number }} Logbook
            </h2>
            <p class="mt-1 text-sm text-gray-500">
                Review your weekly entry, AI insights, and signed logsheet.
            </p>
        </div>
        <div class="mt-4 flex flex-col items-end gap-2 md:ml-4 md:mt-0">
            @php
                $statusConfig = [
                    'draft' => ['label' => 'Draft', 'class' => 'bg-gray-100 text-gray-800', 'icon' => 'file-text'],
                    'submitted' => ['label' => 'Submitted', 'class' => 'bg-amber-100 text-amber-800', 'icon' => 'check'],
                    'pending_review' => ['label' => 'Pending Review', 'class' => 'bg-amber-100 text-amber-800', 'icon' => 'clock'],
                    'approved' => ['label' => 'Approved', 'class' => 'bg-green-100 text-green-800', 'icon' => 'check-circle'],
                    'rejected' => ['label' => 'Rejected', 'class' => 'bg-rose-100 text-rose-800', 'icon' => 'x-circle'],
                ];
                $status = $logbook->status ?? 'draft';
                $config = $statusConfig[$status] ?? $statusConfig['draft'];
            @endphp
            
            <span class="inline-flex items-center rounded-full px-3 py-1.5 text-sm font-medium {{ $config['class'] }} ring-1 ring-inset">
                <i data-lucide="{{ $config['icon'] }}" class="mr-1.5 h-4 w-4"></i>
                {{ $config['label'] }}
            </span>

            <span class="text-xs text-gray-500">
                @if($logbook->submitted_at)
                    Submitted {{ $logbook->submitted_at->format('M d, Y H:i') }}
                @else
                    Not yet submitted
                @endif
            </span>
        </div>
    </div>

    {{-- Main Content Grid --}}
    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Left Column: Entry Text Display --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Entry Text Card --}}
            <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                <div class="px-6 py-5 border-b border-gray-100">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-base font-semibold text-gray-900">Your Entry</h3>
                            <p class="mt-1 text-sm text-gray-500">Full text of your weekly logbook submission.</p>
                        </div>
                        <span class="inline-flex items-center rounded-full bg-indigo-50 px-3 py-1 text-xs font-medium text-indigo-700 ring-1 ring-inset ring-indigo-600/20">
                            <i data-lucide="book-open-text" class="mr-1 h-3.5 w-3.5"></i>
                            Week {{ $logbook->week_number }}
                        </span>
                    </div>
                </div>
                
                <div class="p-6">
                    @if($logbook->entry_text)
                        <div class="rounded-lg border border-gray-200 bg-gray-50/80 p-4 text-sm leading-relaxed text-gray-800 whitespace-pre-wrap">
                            {{ $logbook->entry_text }}
                        </div>
                    @else
                        <div class="text-center py-8">
                            <i data-lucide="file-x" class="h-10 w-10 mx-auto text-gray-300 mb-2"></i>
                            <p class="text-sm text-gray-500">No entry text provided.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Right Column: AI Insights + Logsheet --}}
        <div class="lg:col-span-1 space-y-6">
            {{-- AI Insights Panel --}}
            @php
                $analysis = $logbook->ai_analysis_json ?? [];
            @endphp

            <div class="overflow-hidden rounded-2xl bg-gradient-to-b from-indigo-50 to-white shadow-sm ring-1 ring-indigo-100">
                <div class="px-6 py-5 border-b border-indigo-100">
                    <div class="flex items-center gap-3">
                        <div class="p-2.5 bg-gradient-to-br from-indigo-500 to-[#27233A] rounded-lg text-white shadow-lg">
                            <i data-lucide="sparkles" class="h-5 w-5"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900">AI Insights</h3>
                            <p class="text-xs text-gray-500">Extracted from your entry</p>
                        </div>
                    </div>
                </div>

                <div class="p-6">
                    @if(!empty($analysis))
                        <div class="space-y-5">
                            {{-- Summary --}}
                            @if(!empty($analysis['summary']))
                                <div>
                                    <p class="text-xs font-bold uppercase text-gray-400 tracking-wider mb-2">Summary</p>
                                    <div class="rounded-lg bg-white p-3 ring-1 ring-gray-200">
                                        <p class="text-sm text-gray-600 italic">{{ $analysis['summary'] }}</p>
                                    </div>
                                </div>
                            @endif

                            {{-- Skills Detected --}}
                            @if(!empty($analysis['skills_identified']) && is_array($analysis['skills_identified']))
                                <div>
                                    <p class="text-xs font-bold uppercase text-gray-400 tracking-wider mb-2">Skills Highlighted</p>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($analysis['skills_identified'] as $skill)
                                            <span class="inline-flex items-center rounded-lg bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10">
                                                <i data-lucide="check-circle-2" class="mr-1 h-3.5 w-3.5"></i>
                                                {{ $skill }}
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            {{-- Sentiment Analysis --}}
                            @if(!empty($analysis['sentiment']))
                                <div>
                                    <p class="text-xs font-bold uppercase text-gray-400 tracking-wider mb-2">Sentiment</p>
                                    <div class="flex items-center gap-2">
                                        @php
                                            $sentimentIcon = match($analysis['sentiment']) {
                                                'positive' => 'thumbs-up',
                                                'negative' => 'thumbs-down',
                                                'neutral' => 'minus',
                                                default => 'help-circle'
                                            };
                                            $sentimentColor = match($analysis['sentiment']) {
                                                'positive' => 'bg-green-100 text-green-600',
                                                'negative' => 'bg-rose-100 text-rose-600',
                                                'neutral' => 'bg-gray-100 text-gray-600',
                                                default => 'bg-gray-100 text-gray-600'
                                            };
                                        @endphp
                                        <div class="h-8 w-8 rounded-full {{ $sentimentColor }} flex items-center justify-center">
                                            <i data-lucide="{{ $sentimentIcon }}" class="h-4 w-4"></i>
                                        </div>
                                        <div>
                                            <p class="text-sm font-semibold text-gray-900 capitalize">{{ $analysis['sentiment'] }}</p>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            {{-- Analyzed At Timestamp --}}
                            @if(!empty($analysis['analyzed_at']))
                                <div class="pt-2 border-t border-gray-200">
                                    <p class="text-[11px] text-gray-400">
                                        Last analyzed:
                                        {{ \Illuminate\Support\Carbon::parse($analysis['analyzed_at'])->format('M d, Y H:i') }}
                                    </p>
                                </div>
                            @endif
                        </div>
                    @else
                        <div class="text-center py-6">
                            <i data-lucide="sparkles" class="h-8 w-8 mx-auto text-gray-300 mb-2"></i>
                            <p class="text-sm text-gray-500">No AI analysis available for this entry yet.</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Signed Logsheet Download --}}
            <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                <div class="px-6 py-5 border-b border-gray-100">
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-lg bg-emerald-50 flex items-center justify-center">
                            <i data-lucide="file-download" class="h-5 w-5 text-emerald-600"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900">Signed Logsheet</h3>
                            <p class="text-xs text-gray-500">Download your supervisor-signed PDF</p>
                        </div>
                    </div>
                </div>

                <div class="p-6">
                    @if($logbook->file_path)
                        <div class="rounded-lg bg-gray-50 p-4 mb-4 flex items-center gap-3">
                            <i data-lucide="file-pdf" class="h-8 w-8 text-red-500"></i>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate">
                                    {{ basename($logbook->file_path) }}
                                </p>
                                <p class="text-xs text-gray-500">PDF Document</p>
                            </div>
                        </div>
                        <a
                            href="{{ Storage::disk('public')->url($logbook->file_path) }}"
                            download
                            class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-gradient-to-r from-emerald-500 to-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:from-emerald-600 hover:to-emerald-700 transition-all"
                            target="_blank"
                            rel="noopener"
                        >
                            <i data-lucide="download" class="h-4 w-4"></i>
                            Download Logsheet
                        </a>
                    @else
                        <div class="text-center py-6">
                            <i data-lucide="file-x" class="h-10 w-10 mx-auto text-gray-300 mb-2"></i>
                            <p class="text-sm text-gray-500">No logsheet has been uploaded for this entry.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>