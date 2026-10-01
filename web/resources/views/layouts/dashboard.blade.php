<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Dashboard | EduPulse AI' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 font-sans text-slate-900 antialiased">
    @php
        $role = auth()->user()->role->value;
        $navigation = match ($role) {
            'teacher' => [
                ['label' => 'Dashboard', 'icon' => 'dashboard', 'route' => 'teacher.dashboard'],
                ['label' => 'Students', 'icon' => 'users', 'route' => 'teacher.students.index', 'active' => 'teacher.students.*'],
                ['label' => 'Gradebook', 'icon' => 'quiz', 'route' => 'teacher.grades.index', 'active' => 'teacher.grades.*'],
                ['label' => 'Learning Analytics', 'icon' => 'chart'],
                ['label' => 'Modules', 'icon' => 'book', 'route' => 'teacher.modules.index', 'active' => 'teacher.modules.*'],
                ['label' => 'AI Insights', 'icon' => 'sparkles'],
            ],
            'student' => [
                ['label' => 'Dashboard', 'icon' => 'dashboard', 'route' => 'student.dashboard'],
                ['label' => 'Study Plan', 'icon' => 'calendar'],
                ['label' => 'AI Tutor', 'icon' => 'bot', 'route' => 'student.tutor.index', 'active' => 'student.tutor.*'],
                ['label' => 'Practice Quiz', 'icon' => 'quiz'],
                ['label' => 'Progress', 'icon' => 'trend'],
            ],
            default => [
                ['label' => 'Dashboard', 'icon' => 'dashboard', 'route' => 'admin.dashboard'],
                ['label' => 'Users', 'icon' => 'users', 'route' => 'admin.users.index'],
                ['label' => 'Subjects', 'icon' => 'book', 'route' => 'admin.subjects.index', 'active' => 'admin.subjects.*'],
                ['label' => 'System Health', 'icon' => 'chart'],
            ],
        };
    @endphp

    <div data-sidebar-backdrop class="fixed inset-0 z-40 hidden bg-slate-950/60 backdrop-blur-sm lg:hidden"></div>

    <aside data-sidebar class="fixed inset-y-0 left-0 z-50 flex w-72 -translate-x-full flex-col bg-slate-950 text-white shadow-2xl transition-transform duration-300 lg:translate-x-0">
        <div class="flex h-20 items-center justify-between border-b border-white/10 px-6">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
                <span class="grid h-10 w-10 place-items-center rounded-xl bg-cyan-400 text-sm font-black text-slate-950 shadow-lg shadow-cyan-400/20">EP</span>
                <span class="font-semibold tracking-tight">EduPulse <span class="text-cyan-300">AI</span></span>
            </a>
            <button data-sidebar-close type="button" class="rounded-lg p-2 text-slate-400 hover:bg-white/10 hover:text-white lg:hidden" aria-label="Close navigation">
                <x-icon name="close" />
            </button>
        </div>

        <div class="px-5 pt-6">
            <p class="px-3 text-[10px] font-bold uppercase tracking-[0.24em] text-slate-500">{{ $role }} workspace</p>
        </div>

        <nav class="mt-3 flex-1 space-y-1 overflow-y-auto px-4" aria-label="Primary navigation">
            @foreach ($navigation as $item)
                @php
                    $available = isset($item['route']);
                    $active = $available && request()->routeIs($item['active'] ?? $item['route']);
                @endphp
                <a
                    href="{{ $available ? route($item['route']) : '#' }}"
                    @if (! $available) aria-disabled="true" onclick="return false" @endif
                    class="group flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-medium transition {{ $active ? 'bg-cyan-400 text-slate-950 shadow-lg shadow-cyan-950/30' : 'text-slate-400 hover:bg-white/[0.07] hover:text-white' }} {{ ! $available ? 'cursor-not-allowed opacity-60' : '' }}"
                >
                    <x-icon :name="$item['icon']" class="h-5 w-5 shrink-0" />
                    <span>{{ $item['label'] }}</span>
                    @if (! $available)
                        <span class="ml-auto rounded bg-white/10 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wider">Soon</span>
                    @endif
                </a>
            @endforeach
        </nav>

        <div class="border-t border-white/10 p-4">
            <div class="mb-3 flex items-center gap-3 rounded-xl bg-white/[0.05] p-3">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-gradient-to-br from-cyan-300 to-indigo-400 text-sm font-bold text-slate-950">
                    {{ strtoupper(substr(auth()->user()->first_name, 0, 1).substr(auth()->user()->last_name, 0, 1)) }}
                </span>
                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-white">{{ auth()->user()->full_name }}</p>
                    <p class="truncate text-xs text-slate-500">{{ auth()->user()->email }}</p>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-400 transition hover:bg-rose-500/10 hover:text-rose-300">
                    <x-icon name="logout" class="h-5 w-5" />
                    Log out
                </button>
            </form>
        </div>
    </aside>

    <div class="min-h-screen lg:pl-72">
        <header class="sticky top-0 z-30 flex h-20 items-center justify-between border-b border-slate-200/80 bg-white/90 px-5 backdrop-blur-xl sm:px-8">
            <div class="flex items-center gap-3">
                <button data-sidebar-open type="button" class="rounded-xl border border-slate-200 p-2.5 text-slate-600 shadow-sm lg:hidden" aria-label="Open navigation">
                    <x-icon name="menu" />
                </button>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-cyan-600">{{ now()->format('l') }}</p>
                    <p class="mt-1 text-sm text-slate-500">{{ now()->format('F j, Y') }}</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <span class="hidden rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-100 sm:inline-flex">System ready</span>
                <button type="button" class="relative rounded-xl border border-slate-200 p-2.5 text-slate-500 shadow-sm transition hover:bg-slate-50" aria-label="Notifications">
                    <x-icon name="bell" />
                </button>
            </div>
        </header>

        <main class="px-5 py-7 sm:px-8 lg:px-10 lg:py-9">
            @if (session('status'))
                <div class="mb-6 flex items-start gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3.5 text-sm text-emerald-800 shadow-sm" role="status">
                    <span class="mt-0.5 grid h-5 w-5 shrink-0 place-items-center rounded-full bg-emerald-600 text-white"><x-icon name="check" class="h-3.5 w-3.5" /></span>
                    <span>{{ session('status') }}</span>
                </div>
            @endif
            @yield('content')
        </main>
    </div>
</body>
</html>
