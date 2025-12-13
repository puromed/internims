<?php

use Livewire\Volt\Component;

new class extends Component {
    public function getUnreadNotificationsProperty()
    {
        return auth()->user()->unreadNotifications()->limit(5)->get();
    }

    public function getUnreadCountProperty()
    {
        return auth()->user()->unreadNotifications()->count();
    }

    public function markAsRead(string $notificationId): void
    {
        $notification = auth()->user()->unreadNotifications()->where('id', $notificationId)->first();
        $notification?->markAsRead();
    }

    public function markAllRead(): void
    {
        auth()->user()->unreadNotifications->markAsRead();
    }
}; ?>

<flux:dropdown position="bottom" align="start">
    {{-- Bell Button --}}
    <flux:button variant="ghost" square class="relative">
        <i data-lucide="bell" class="size-5"></i>
        @if($this->unreadCount > 0)
            <span class="absolute top-0.5 right-0.5 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[10px] font-bold text-white">
                {{ $this->unreadCount > 9 ? '9+' : $this->unreadCount }}
            </span>
        @endif
    </flux:button>

    {{-- Dropdown Menu --}}
    <flux:menu class="w-80">
        <div class="flex items-center justify-between px-3 py-2 border-b border-zinc-200 dark:border-zinc-700">
            <span class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Notifications</span>
            @if($this->unreadCount > 0)
                <button wire:click="markAllRead" class="text-xs text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">
                    Mark all read
                </button>
            @endif
        </div>

        <div class="max-h-72 overflow-y-auto">
            @forelse($this->unreadNotifications as $notification)
                <flux:menu.item class="flex gap-2 items-start">
                    <div class="flex-1 min-w-0">
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">
                            {{ $notification->created_at->diffForHumans() }}
                        </p>
                        <p class="text-sm text-zinc-900 dark:text-zinc-100 truncate">
                            {{ $notification->data['message'] ?? 'New notification' }}
                        </p>
                        @if(isset($notification->data['comment']))
                            <p class="text-xs text-zinc-600 dark:text-zinc-400 italic truncate">
                                "{{ Str::limit($notification->data['comment'], 40) }}"
                            </p>
                        @endif
                    </div>
                    <button wire:click.stop="markAsRead('{{ $notification->id }}')" class="shrink-0 text-zinc-400 hover:text-indigo-600 dark:hover:text-indigo-400" title="Mark read">
                        <i data-lucide="check" class="h-4 w-4"></i>
                    </button>
                </flux:menu.item>
            @empty
                <div class="px-4 py-6 text-center text-sm text-zinc-500 dark:text-zinc-400">
                    No new notifications
                </div>
            @endforelse
        </div>

        <flux:menu.separator />
        
        <div class="px-3 py-2 text-center">
            <a href="#" class="text-xs font-medium text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-200">
                View all history
            </a>
        </div>
    </flux:menu>
</flux:dropdown>
