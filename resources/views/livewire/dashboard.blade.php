<?php

use App\Models\Application;
use App\Models\EligibilityDoc;
use App\Models\Internship;
use App\Models\LogbookEntry;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Volt\Component;

new class extends Component {
    public array $stats = [];
    public array $activities = [];
    public array $stepper = [];
    public array $actions = [];
    public array $dates = [];
    public int $missingDocs = 0;
    public int $requiredDocs = 0;
    public int $uploadedDocs = 0;

    public function mount(): void
    {
        $user = Auth::user();

        // Eligibility
        $requiredTypes = ['resume', 'transcript', 'offer_letter'];
        $this->requiredDocs = count($requiredTypes);
        $docs = $user->eligibilityDocs()->get();
        $approvedDocs = $docs->where('status', 'approved')->count();
        $this->uploadedDocs = $docs->count();
        $this->missingDocs = max($this->requiredDocs - $approvedDocs, 0);
        $eligibilityComplete = $approvedDocs >= $this->requiredDocs;

        // Placement
        $placement = $user->internships()->latest('start_date')->first();
        $placementUnlocked = $eligibilityComplete;
        $logbooksUnlocked = (bool) $placement;
        $weeksCompleted = $placement && $placement->start_date
            ? round(min(now()->diffInWeeks($placement->start_date), 24), 1)
            : 0;

        // Logbooks
        $logbooksQuery = $user->logbookEntries();
        $logbookTotal = $logbooksQuery->count();
        $logbookApproved = (clone $logbooksQuery)->where('status', 'approved')->count();
        $logbookPending = (clone $logbooksQuery)->whereIn('status', ['submitted', 'pending_review'])->count();
        $logbookDraft = (clone $logbooksQuery)->where('status', 'draft')->count();

        // Notifications
        $unreadNotifications = DB::table('notifications')
            ->where('notifiable_id', $user->id)
            ->whereNull('read_at')
            ->count();

        // Stage tracking
        $currentStageIndex = 0;
        if ($eligibilityComplete) {
            $currentStageIndex = $placement ? ($logbookTotal > 0 ? 3 : 2) : 1;
        }

        $this->stepper = [
            ['num' => 1, 'label' => 'Eligibility', 'active' => $currentStageIndex >= 0],
            ['num' => 2, 'label' => 'Placement', 'active' => $currentStageIndex >= 1],
            ['num' => 3, 'label' => 'Logbooks', 'active' => $currentStageIndex >= 2],
            ['num' => 4, 'label' => 'Completion', 'active' => $currentStageIndex >= 3],
        ];

        // Stats
        $this->stats = [
            [
                'label' => 'Current Stage',
                'value' => ($currentStageIndex + 1) . ' of 4',
                'suffix' => 'stages',
                'badge' => ['text' => 'In Progress', 'color' => 'yellow', 'icon' => 'clock'],
            ],
            [
                'label' => 'Weeks Completed',
                'value' => $weeksCompleted,
                'suffix' => '/ 24 weeks',
                'badge' => ['text' => $weeksCompleted ? intval(($weeksCompleted / 24) * 100) . '%' : '0%', 'color' => 'gray', 'icon' => null],
            ],
            [
                'label' => 'Documents',
                'value' => $this->uploadedDocs,
                'suffix' => "/ {$this->requiredDocs} uploaded",
                'badge' => ['text' => $this->missingDocs ? 'Incomplete' : 'Complete', 'color' => $this->missingDocs ? 'red' : 'green', 'icon' => $this->missingDocs ? 'exclamation-circle' : 'check'],
            ],
            [
                'label' => 'Logbooks',
                'value' => $logbookTotal,
                'suffix' => '/ 24 weeks',
                'badge' => [
                    'text' => $logbookApproved . ' approved',
                    'color' => $logbookApproved > 0 ? 'green' : 'gray',
                    'icon' => $logbookApproved > 0 ? 'check' : null
                ],
            ],
            [
                'label' => 'Notifications',
                'value' => $unreadNotifications,
                'suffix' => 'new',
                'badge' => ['text' => $unreadNotifications ? 'Unread' : 'All read', 'color' => $unreadNotifications ? 'blue' : 'gray', 'icon' => 'bell'],
            ],
        ];

        // Activity
        $activities = collect();

        // 1. Notifications
        $notifs = DB::table('notifications')
            ->where('notifiable_id', $user->id)
            ->latest()
            ->limit(10)
            ->get()
            ->map(function ($n) {
                $data = json_decode($n->data, true) ?? [];
                return [
                    'icon' => $data['icon'] ?? 'bell',
                    'iconBg' => $data['iconBg'] ?? 'blue',
                    'text' => $data['text'] ?? ($data['message'] ?? 'Notification'),
                    'highlight' => $data['highlight'] ?? ($data['title'] ?? ''),
                    'time' => \Illuminate\Support\Carbon::parse($n->created_at),
                ];
            });
        $activities = $activities->concat($notifs);

        // 2. Eligibility Docs
        $docs = $user->eligibilityDocs()->latest('updated_at')->limit(5)->get()->map(function ($doc) {
            $statusText = match ($doc->status) {
                'approved' => 'Approved',
                'rejected' => 'Rejected',
                default => 'Uploaded',
            };
            $icon = match ($doc->status) {
                'approved' => 'check',
                'rejected' => 'x-mark',
                default => 'document-text',
            };
            $bg = match ($doc->status) {
                'approved' => 'green',
                'rejected' => 'red',
                default => 'blue',
            };

            return [
                'icon' => $icon,
                'iconBg' => $bg,
                'text' => $statusText,
                'highlight' => ucfirst(str_replace('_', ' ', $doc->type)),
                'time' => $doc->updated_at,
            ];
        });
        $activities = $activities->concat($docs);

        // 3. Logbooks
        $logbooks = $user->logbookEntries()->latest('updated_at')->limit(5)->get()->map(function ($entry) {
            $statusText = match ($entry->status) {
                'approved' => 'Approved',
                'rejected', 'needs_revision' => 'Revision Requested',
                'submitted', 'pending_review' => 'Submitted',
                default => 'Updated',
            };
            $icon = match ($entry->status) {
                'approved' => 'check',
                'rejected', 'needs_revision' => 'exclamation-triangle',
                'submitted', 'pending_review' => 'paper-airplane',
                default => 'pencil',
            };
            $bg = match ($entry->status) {
                'approved' => 'green',
                'rejected', 'needs_revision' => 'yellow',
                'submitted', 'pending_review' => 'blue',
                default => 'gray',
            };

            return [
                'icon' => $icon,
                'iconBg' => $bg,
                'text' => $statusText,
                'highlight' => "Week {$entry->week_number} Logbook",
                'time' => $entry->updated_at,
            ];
        });
        $activities = $activities->concat($logbooks);

        // 4. Internship
        $internships = $user->internships()->latest('updated_at')->limit(5)->get()->map(function ($internship) {
            return [
                'icon' => 'briefcase',
                'iconBg' => 'indigo',
                'text' => 'Updated placement at',
                'highlight' => $internship->company_name ?: 'TBD',
                'time' => $internship->updated_at,
            ];
        });
        $activities = $activities->concat($internships);

        // Sort and format
        $this->activities = $activities->sortByDesc('time')
            ->take(15)
            ->map(function ($a) {
                $a['time'] = $a['time']->diffForHumans();

                return $a;
            })
            ->values()
            ->all();

        if (empty($this->activities)) {
            $this->activities = [
                ['icon' => 'user-plus', 'iconBg' => 'indigo', 'text' => 'Registered for', 'highlight' => 'Spring 2025', 'time' => '1d ago'],
                ['icon' => 'play', 'iconBg' => 'gray', 'text' => 'Started', 'highlight' => 'internship journey', 'time' => '1d ago'],
            ];
        }

        // Actions
        $this->actions = [
            [
                'title' => 'Upload Eligibility Documents',
                'description' => 'Submit your Resume and Academic Transcript to complete Stage 1 verification.',
                'icon' => 'document-text',
                'accent' => 'rose',
                'status' => $this->missingDocs ? "{$this->missingDocs} documents missing" : 'Complete',
                'status_color' => $this->missingDocs ? 'yellow' : 'green',
                'locked' => false,
                'url' => route('eligibility.index'),
            ],
            [
                'title' => 'Register Placement Company',
                'description' => 'Complete Stage 1 to unlock placement registration.',
                'icon' => 'briefcase',
                'accent' => 'gray',
                'status' => $placementUnlocked ? 'Open' : 'Locked',
                'status_color' => $placementUnlocked ? 'green' : 'gray',
                'locked' => !$placementUnlocked,
                'url' => route('placement.index'),
            ],
            [
                'title' => 'Submit Weekly Logbooks',
                'description' => $logbooksUnlocked
                    ? "Track your weekly progress. {$logbookApproved} approved, {$logbookPending} pending."
                    : 'Complete Stage 2 to start submitting logbooks.',
                'icon' => 'book-open',
                'accent' => 'gray',
                'status' => $logbooksUnlocked ? "{$logbookTotal} / 24 submitted" : 'Locked',
                'status_color' => $logbooksUnlocked ? ($logbookTotal > 0 ? 'green' : 'yellow') : 'gray',
                'locked' => !$logbooksUnlocked,
                'url' => route('logbooks.index'),
            ],
        ];

        // Dates
        $this->dates = \App\Models\ImportantDate::query()
            ->where('semester', \App\Services\SemesterService::getCurrentSemesterCode())
            ->orderBy('date')
            ->get()
            ->map(fn ($d) => [
                'title' => $d->title,
                'date' => $d->date->format('M d, Y'),
            ])
            ->toArray();
    }
}; ?>

<div>
    {{-- Page Header --}}
    <div class="mb-8">
        <flux:heading size="xl">Dashboard Overview</flux:heading>
        <flux:subheading>Welcome back, {{ auth()->user()->name ?? 'Student' }}! Here's your internship progress.</flux:subheading>
    </div>

    {{-- Progress Stepper --}}
    <nav aria-label="Progress" class="mb-8">
        <ol role="list" class="divide-y divide-gray-300 rounded-xl border border-gray-300 bg-white md:flex md:divide-y-0 shadow-sm dark:border-gray-700 dark:bg-zinc-900 dark:divide-gray-700">
            @foreach($stepper as $step)
                <li class="relative md:flex md:flex-1">
                    <a href="#" class="group flex w-full items-center {{ !$step['active'] ? 'opacity-60 cursor-not-allowed' : '' }}">
                        <span class="flex items-center px-6 py-4 text-sm font-medium">
                            <span class="flex size-10 shrink-0 items-center justify-center rounded-full border-2 {{ $step['active'] ? 'border-indigo-600 bg-indigo-600' : 'border-gray-300 bg-white dark:border-gray-600 dark:bg-zinc-800' }}">
                                <span class="{{ $step['active'] ? 'text-white' : 'text-gray-500 dark:text-gray-400' }} font-bold">{{ $step['num'] }}</span>
                            </span>
                            <span class="ml-4 text-sm font-medium {{ $step['active'] ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-500 dark:text-gray-400' }}">{{ $step['label'] }}</span>
                        </span>
                    </a>
                    @if($step['num'] < 4)
                        <div class="absolute top-0 right-0 hidden h-full w-5 md:block" aria-hidden="true">
                            <svg class="size-full text-gray-300 dark:text-gray-700" viewBox="0 0 22 80" fill="none" preserveAspectRatio="none">
                                <path d="M0 -2L20 40L0 82" vector-effect="non-scaling-stroke" stroke="currentcolor" stroke-linejoin="round" />
                            </svg>
                        </div>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>

    {{-- Stats Grid --}}
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-8">
        @foreach($stats as $stat)
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-zinc-900">
                <dt class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">{{ $stat['label'] }}</dt>
                <dd class="mt-2 flex items-baseline justify-between md:block lg:flex">
                    <div class="flex items-baseline text-2xl font-bold {{ $loop->first ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-900 dark:text-white' }}">
                        {{ $stat['value'] }}
                        <span class="ml-2 text-sm font-medium text-gray-500 dark:text-gray-400">{{ $stat['suffix'] }}</span>
                    </div>
                    
                    @php
                        $badgeColor = match($stat['badge']['color']) {
                            'yellow' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-200',
                            'red'    => 'bg-rose-100 text-rose-800 dark:bg-rose-900/50 dark:text-rose-200',
                            'blue'   => 'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-200',
                            'green'  => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-200',
                            default  => 'bg-gray-100 text-gray-800 dark:bg-zinc-800 dark:text-zinc-300',
                        };
                    @endphp

                    <div class="inline-flex items-baseline rounded-full px-2.5 py-0.5 text-sm font-medium md:mt-2 lg:mt-0 {{ $badgeColor }}">
                        @if($stat['badge']['icon'])
                            <flux:icon name="{{ $stat['badge']['icon'] }}" class="mr-1 size-3 self-center" />
                        @endif
                        {{ $stat['badge']['text'] }}
                    </div>
                </dd>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
        {{-- Left Column: Required Actions --}}
        <div class="lg:col-span-2">
            <flux:heading size="lg" class="mb-4">Required Actions</flux:heading>
            <div class="divide-y divide-gray-200 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-zinc-900 dark:divide-gray-700">
                @include('livewire.partials.dashboard-actions', ['actions' => $actions])
            </div>
        </div>

        {{-- Right Column: Activity Feed --}}
        <div class="lg:col-span-1">
            <flux:heading size="lg" class="mb-4">Recent Activity</flux:heading>
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm p-6 dark:border-gray-700 dark:bg-zinc-900">
                <div class="max-h-[400px] overflow-y-auto pr-2 custom-scrollbar">
                    @include('livewire.partials.dashboard-activity', ['activities' => $activities])
                </div>
            </div>
            @include('livewire.partials.dashboard-dates', ['dates' => $dates])
        </div>
    </div>
</div>
