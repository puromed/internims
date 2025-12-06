<ul role="list" class="-mb-8">
    @foreach($activities as $activity)
        <li>
            <div class="relative {{ !$loop->last ? 'pb-8' : '' }}">
                @if(!$loop->last)
                    <span class="absolute left-4 top-4 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
                @endif
                <div class="relative flex space-x-3">
                    <div>
                        <span class="flex size-8 items-center justify-center rounded-full
                            @if($activity['iconBg'] === 'green') bg-green-500
                            @elseif($activity['iconBg'] === 'blue') bg-blue-500
                            @elseif($activity['iconBg'] === 'indigo') bg-indigo-500
                            @else bg-gray-400
                            @endif">
                            <i data-lucide="{{ $activity['icon'] }}" class="size-4 text-white"></i>
                        </span>
                    </div>
                    <div class="flex min-w-0 flex-1 justify-between space-x-4 pt-1.5">
                        <div>
                            <p class="text-sm text-gray-500">{{ $activity['text'] }} <span class="font-medium text-gray-900">{{ $activity['highlight'] }}</span></p>
                        </div>
                        <div class="whitespace-nowrap text-right text-sm text-gray-500">
                            <time>{{ $activity['time'] }}</time>
                        </div>
                    </div>
                </div>
            </div>
        </li>
    @endforeach
</ul>

