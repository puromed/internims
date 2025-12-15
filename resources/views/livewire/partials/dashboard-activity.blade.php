<ul role="list" class="-mb-8">
    @foreach($activities as $activity)
        <li>
            <div class="relative {{ !$loop->last ? 'pb-8' : '' }}">
                @if(!$loop->last)
                    <span class="absolute left-4 top-4 -ml-px h-full w-0.5 bg-gray-200 dark:bg-gray-700" aria-hidden="true"></span>
                @endif
                <div class="relative flex space-x-3">
                    <div>
                        <span class="flex size-8 items-center justify-center rounded-full
                            @if($activity['iconBg'] === 'green') bg-emerald-500
                            @elseif($activity['iconBg'] === 'blue') bg-blue-500
                            @elseif($activity['iconBg'] === 'indigo') bg-indigo-500
                            @else bg-gray-400 dark:bg-gray-600
                            @endif">
                            <flux:icon name="{{ $activity['icon'] }}" class="size-4 text-white" />
                        </span>
                    </div>
                    <div class="flex min-w-0 flex-1 justify-between space-x-4 pt-1.5">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $activity['text'] }} <span class="font-medium text-gray-900 dark:text-white">{{ $activity['highlight'] }}</span></p>
                        </div>
                        <div class="whitespace-nowrap text-right text-sm text-gray-500 dark:text-gray-400">
                            <time>{{ $activity['time'] }}</time>
                        </div>
                    </div>
                </div>
            </div>
        </li>
    @endforeach
</ul>

