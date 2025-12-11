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

        $this->logbook = $logbook->loadMissing(['user', 'supervisor']);
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
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-300">
                Review your weekly entry, AI insights, and signed logsheet.
            </p>
        </div>
        <div class="mt-4 flex flex-col items-end gap-2 md:ml-4 md:mt-0">
            @php
                $statusConfig = [
                    'draft' => ['label' => 'Draft', 'class' => 'bg-gray-100 text-gray-800', 'dark_class' => 'dark:bg-gray-800/70 dark:text-gray-100', 'icon' => 'file-text'],
                    'submitted' => ['label' => 'Submitted', 'class' => 'bg-amber-100 text-amber-800', 'dark_class' => 'dark:bg-amber-500/10 dark:text-amber-200', 'icon' => 'check'],
                    'pending_review' => ['label' => 'Pending Review', 'class' => 'bg-amber-100 text-amber-800', 'dark_class' => 'dark:bg-amber-500/10 dark:text-amber-200', 'icon' => 'clock'],
                    'approved' => ['label' => 'Approved', 'class' => 'bg-green-100 text-green-800', 'dark_class' => 'dark:bg-green-500/10 dark:text-green-200', 'icon' => 'check-circle'],
                    'rejected' => ['label' => 'Rejected', 'class' => 'bg-rose-100 text-rose-800', 'dark_class' => 'dark:bg-rose-500/10 dark:text-rose-200', 'icon' => 'x-circle'],
                ];
                $status = $logbook->status ?? 'draft';
                $config = $statusConfig[$status] ?? $statusConfig['draft'];

                $supervisorStatusConfig = [
                    'revision_requested' => ['label' => 'Revision requested', 'class' => 'bg-rose-100 text-rose-800', 'dark_class' => 'dark:bg-rose-500/10 dark:text-rose-200', 'icon' => 'rotate-ccw'],
                    'verified' => ['label' => 'Verified', 'class' => 'bg-green-100 text-green-800', 'dark_class' => 'dark:bg-green-500/10 dark:text-green-200', 'icon' => 'check-circle-2'],
                    'pending' => ['label' => 'Supervisor pending', 'class' => 'bg-amber-100 text-amber-800', 'dark_class' => 'dark:bg-amber-500/10 dark:text-amber-200', 'icon' => 'hourglass'],
                ];
                $supervisorStatus = $logbook->supervisor_status ?? null;
                $supervisorBadge = $supervisorStatus ? ($supervisorStatusConfig[$supervisorStatus] ?? null) : null;
            @endphp
            
            <span class="inline-flex items-center rounded-full px-3 py-1.5 text-sm font-medium {{ $config['class'] }} {{ $config['dark_class'] ?? '' }} ring-1 ring-inset dark:ring-white/20">
                <i data-lucide="{{ $config['icon'] }}" class="mr-1.5 h-4 w-4"></i>
                {{ $config['label'] }}
            </span>

            @if($supervisorBadge)
                <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $supervisorBadge['class'] }} {{ $supervisorBadge['dark_class'] ?? '' }} ring-1 ring-inset dark:ring-white/20">
                    <i data-lucide="{{ $supervisorBadge['icon'] }}" class="mr-1 h-3.5 w-3.5"></i>
                    {{ $supervisorBadge['label'] }}
                </span>
            @endif

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
            @if($supervisorStatus === 'revision_requested')
                <div class="rounded-2xl border border-rose-200 bg-rose-50/70 p-5 text-rose-800 shadow-sm dark:border-rose-400/40 dark:bg-rose-950/40 dark:text-rose-100">
                    <div class="flex items-start gap-3">
                        <div class="mt-1 flex h-9 w-9 items-center justify-center rounded-full bg-rose-100 dark:bg-rose-900/60">
                            <i data-lucide="rotate-ccw" class="h-5 w-5"></i>
                        </div>
                        <div class="space-y-2 text-sm">
                            <div>
                                <p class="font-semibold text-rose-900 dark:text-rose-100">Supervisor revision requested</p>
                                <p class="text-rose-700 dark:text-rose-200">Please review the feedback below, update your logbook entry, and resubmit for verification.</p>
                            </div>
                            @if($logbook->supervisor_comment)
                                <div class="rounded-lg border border-rose-200 bg-white/60 p-3 text-rose-800 dark:border-rose-400/30 dark:bg-rose-900/40 dark:text-rose-100">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-rose-500 dark:text-rose-300">Supervisor feedback</p>
                                    <p class="mt-1 text-sm leading-relaxed text-rose-800 dark:text-rose-100">{{ $logbook->supervisor_comment }}</p>
                                </div>
                            @endif
                            <div class="flex flex-wrap items-center gap-4 text-xs text-rose-600 dark:text-rose-200">
                                @if($logbook->reviewed_by && $logbook->supervisor)
                                    <span class="inline-flex items-center gap-1">
                                        <i data-lucide="user-check" class="h-3.5 w-3.5"></i>
                                        Reviewed by {{ $logbook->supervisor->name }}
                                    </span>
                                @endif
                                @if($logbook->reviewed_at)
                                    <span class="inline-flex items-center gap-1">
                                        <i data-lucide="calendar-clock" class="h-3.5 w-3.5"></i>
                                        {{ $logbook->reviewed_at->format('M d, Y H:i') }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @elseif($supervisorStatus === 'verified')
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50/70 p-5 text-emerald-800 shadow-sm dark:border-emerald-400/40 dark:bg-emerald-950/40 dark:text-emerald-100">
                    <div class="flex items-start gap-3">
                        <div class="mt-1 flex h-9 w-9 items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-900/60">
                            <i data-lucide="check-circle-2" class="h-5 w-5"></i>
                        </div>
                        <div class="space-y-2 text-sm">
                            <div>
                                <p class="font-semibold text-emerald-900 dark:text-emerald-100">Supervisor verified</p>
                                <p class="text-emerald-700 dark:text-emerald-200">Great work! Your supervisor has verified this entry.</p>
                            </div>
                            @if($logbook->supervisor_comment)
                                <div class="rounded-lg border border-emerald-200 bg-white/60 p-3 text-emerald-800 dark:border-emerald-400/30 dark:bg-emerald-900/40 dark:text-emerald-100">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-emerald-500 dark:text-emerald-300">Supervisor note</p>
                                    <p class="mt-1 text-sm leading-relaxed text-emerald-800 dark:text-emerald-100">{{ $logbook->supervisor_comment }}</p>
                                </div>
                            @endif
                            <div class="flex flex-wrap items-center gap-4 text-xs text-emerald-600 dark:text-emerald-200">
                                @if($logbook->reviewed_by && $logbook->supervisor)
                                    <span class="inline-flex items-center gap-1">
                                        <i data-lucide="user-check" class="h-3.5 w-3.5"></i>
                                        Verified by {{ $logbook->supervisor->name }}
                                    </span>
                                @endif
                                @if($logbook->reviewed_at)
                                    <span class="inline-flex items-center gap-1">
                                        <i data-lucide="calendar-clock" class="h-3.5 w-3.5"></i>
                                        {{ $logbook->reviewed_at->format('M d, Y H:i') }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Entry Text Card --}}
            <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200 dark:bg-slate-900/80 dark:ring-white/10">
                <div class="px-6 py-5 border-b border-gray-100 dark:border-white/10">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Your Entry</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-300">Full text of your weekly logbook submission.</p>
                        </div>
                        <span class="inline-flex items-center rounded-full bg-indigo-50 px-3 py-1 text-xs font-medium text-indigo-700 ring-1 ring-inset ring-indigo-600/20">
                            <i data-lucide="book-open-text" class="mr-1 h-3.5 w-3.5"></i>
                            Week {{ $logbook->week_number }}
                        </span>
                    </div>
                </div>
                
                <div class="p-6">
                    @if($logbook->entry_text)
                        <div class="rounded-lg border border-gray-200 bg-gray-50/80 p-4 text-sm leading-relaxed text-gray-800 whitespace-pre-wrap dark:border-white/10 dark:bg-slate-900/60 dark:text-gray-100">
                            {{ $logbook->entry_text }}
                        </div>
                    @else
                        <div class="text-center py-8">
                            <i data-lucide="file-x" class="h-10 w-10 mx-auto text-gray-300 mb-2"></i>
                            <p class="text-sm text-gray-500 dark:text-gray-300">No entry text provided.</p>
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

            <div class="overflow-hidden rounded-2xl bg-gradient-to-b from-indigo-50 to-white shadow-sm ring-1 ring-indigo-100 dark:from-[#1f2140] dark:to-[#111227] dark:ring-indigo-500/20">
                <div class="px-6 py-5 border-b border-indigo-100 dark:border-indigo-500/20">
                    <div class="flex items-center gap-3">
                        <div class="p-2.5 bg-gradient-to-br from-indigo-500 to-[#27233A] rounded-lg text-white shadow-lg">
                            <i data-lucide="sparkles" class="h-5 w-5"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900 dark:text-gray-100">AI Insights</h3>
                            <p class="text-xs text-gray-500 dark:text-indigo-200/80">Extracted from your entry</p>
                        </div>
                    </div>
                </div>

                <div class="p-6">
                    @if(!empty($analysis))
                        <div class="space-y-5">
                            {{-- Summary --}}
                            @if(!empty($analysis['summary']))
                                <div>
                                    <p class="text-xs font-bold uppercase text-gray-400 tracking-wider mb-2 dark:text-indigo-200/80">Summary</p>
                                    <div class="rounded-lg bg-white p-3 ring-1 ring-gray-200 dark:bg-indigo-900/40 dark:ring-indigo-500/30">
                                        <p class="text-sm text-gray-600 italic dark:text-indigo-100">{{ $analysis['summary'] }}</p>
                                    </div>
                                </div>
                            @endif

                            {{-- Skills Detected --}}
                            @if(!empty($analysis['skills_identified']) && is_array($analysis['skills_identified']))
                                <div>
                                    <p class="text-xs font-bold uppercase text-gray-400 tracking-wider mb-2 dark:text-indigo-200/80">Skills Highlighted</p>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($analysis['skills_identified'] as $skill)
                                            <span class="inline-flex items-center rounded-lg bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10 dark:bg-blue-500/10 dark:text-blue-200 dark:ring-blue-400/30">
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
                                    <p class="text-xs font-bold uppercase text-gray-400 tracking-wider mb-2 dark:text-indigo-200/80">Sentiment</p>
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
                                            $sentimentDarkClasses = match($analysis['sentiment']) {
                                                'positive' => 'dark:bg-green-500/10 dark:text-green-200',
                                                'negative' => 'dark:bg-rose-500/10 dark:text-rose-200',
                                                'neutral' => 'dark:bg-gray-500/10 dark:text-gray-200',
                                                default => 'dark:bg-gray-500/10 dark:text-gray-200'
                                            };
                                        @endphp
                                        <div class="h-8 w-8 rounded-full {{ $sentimentColor }} {{ $sentimentDarkClasses }} flex items-center justify-center">
                                            <i data-lucide="{{ $sentimentIcon }}" class="h-4 w-4"></i>
                                        </div>
                                        <div>
                                            <p class="text-sm font-semibold text-gray-900 capitalize dark:text-gray-100">{{ $analysis['sentiment'] }}</p>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            {{-- Analyzed At Timestamp --}}
                            @if(!empty($analysis['analyzed_at']))
                                <div class="pt-2 border-t border-gray-200 dark:border-indigo-500/20">
                                    <p class="text-[11px] text-gray-400 dark:text-indigo-200/80">
                                        Last analyzed:
                                        {{ \Illuminate\Support\Carbon::parse($analysis['analyzed_at'])->format('M d, Y H:i') }}
                                    </p>
                                </div>
                            @endif
                        </div>
                    @else
                        <div class="text-center py-6">
                            <i data-lucide="sparkles" class="h-8 w-8 mx-auto text-gray-300 mb-2"></i>
                            <p class="text-sm text-gray-500 dark:text-gray-300">No AI analysis available for this entry yet.</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Signed Logsheet Download --}}
            <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200 dark:bg-slate-900/80 dark:ring-white/10">
                <div class="px-6 py-5 border-b border-gray-100 dark:border-white/10">
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-lg bg-emerald-50 flex items-center justify-center">
                            <i data-lucide="file-download" class="h-5 w-5 text-emerald-600"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900 dark:text-gray-100">Signed Logsheet</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-300">Download your supervisor-signed PDF</p>
                        </div>
                    </div>
                </div>

                <div class="p-6">
                    @if($logbook->file_path)
                        <div class="rounded-lg bg-gray-50 p-4 mb-4 flex items-center gap-3 dark:bg-slate-900/60">
                            <i data-lucide="file-pdf" class="h-8 w-8 text-red-500"></i>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate dark:text-gray-100">
                                    {{ basename($logbook->file_path) }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-300">PDF Document</p>
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
                            <p class="text-sm text-gray-500 dark:text-gray-300">No logsheet has been uploaded for this entry.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>