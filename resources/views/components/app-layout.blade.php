@props(['title' => null, 'nav' => [], 'flash' => session('status')])

<!doctype html>
<html lang="en" class="h-full bg-gray-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? $title.' | IMS' : 'IMS' }}</title>
    @vite(['resources/css/app.css','resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen text-gray-900 antialiased">
<div x-data="{ sidebarOpen:false }" class="min-h-screen flex">
    <div class="fixed inset-0 bg-gray-900/40 backdrop-blur-sm transition lg:hidden"
         x-show="sidebarOpen" x-cloak @click="sidebarOpen=false"></div>

    <aside class="fixed inset-y-0 left-0 z-30 w-72 bg-white border-r border-gray-200 flex flex-col lg:translate-x-0 transition"
           :class="{ '-translate-x-full': !sidebarOpen, 'translate-x-0': sidebarOpen }" x-cloak>
        <div class="px-6 py-4 flex items-center justify-between border-b border-gray-200">
            <span class="text-lg font-semibold text-indigo-600">Intern IMS</span>
            <button class="lg:hidden p-2 text-gray-600 hover:text-gray-900" @click="sidebarOpen=false">
                <i data-lucide="x" class="h-5 w-5"></i>
            </button>
        </div>
        <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
            @foreach($nav as $item)
                @php $active = request()->routeIs($item['active'] ?? ''); @endphp
                <a href="{{ $item['href'] ?? '#' }}"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium {{ $active ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50 hover:text-indigo-600' }}">
                    <i data-lucide="{{ $item['icon'] ?? 'circle' }}" class="h-4 w-4"></i>
                    <span>{{ $item['label'] }}</span>
                    @if(!empty($item['badge']))
                        <span class="ml-auto inline-flex items-center rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-semibold text-indigo-700">
                            {{ $item['badge'] }}
                        </span>
                    @endif
                </a>
            @endforeach
        </nav>
    </aside>

    <div class="lg:pl-72 flex-1 min-w-0">
        <header class="sticky top-0 z-20 bg-white/90 backdrop-blur border-b border-gray-200">
            <div class="flex items-center justify-between px-6 py-4 gap-4">
                <div class="flex items-center gap-3">
                    <button class="lg:hidden p-2 rounded-lg border border-gray-200 text-gray-700" @click="sidebarOpen=true">
                        <i data-lucide="panel-left" class="h-5 w-5"></i>
                    </button>
                    <div>
                        <p class="text-xs text-gray-500">Internship Management</p>
                        <h1 class="text-lg font-semibold">{{ $title ?? 'Dashboard' }}</h1>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <button class="relative p-2 rounded-full border border-gray-200 text-gray-700 hover:text-indigo-600">
                        <i data-lucide="bell" class="h-5 w-5"></i>
                    </button>
                    <div class="flex items-center gap-3">
                        <div class="text-right">
                            <p class="text-sm font-semibold">{{ auth()->user()->name ?? 'Student' }}</p>
                            <p class="text-xs text-gray-500">{{ auth()->user()->role ?? 'Intern' }}</p>
                        </div>
                        <div class="h-10 w-10 rounded-full bg-indigo-600 text-white grid place-content-center text-sm font-semibold">
                            {{ \Illuminate\Support\Str::of(auth()->user()->name ?? 'IN')->substr(0,2)->upper() }}
                        </div>
                    </div>
                </div>
            </div>
            @if($flash)
                <div class="px-6 pb-4">
                    <div class="rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800 flex items-center gap-2">
                        <i data-lucide="check-circle-2" class="h-4 w-4"></i>
                        <span>{{ $flash }}</span>
                    </div>
                </div>
            @endif
        </header>

        <main class="p-6">
            {{ $slot }}
        </main>
    </div>
</div>
@livewireScripts
<script type="module">
    lucide.createIcons();
</script>
</body>
</html>
