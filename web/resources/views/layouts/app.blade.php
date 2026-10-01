<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'EduPulse AI' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-950 font-sans text-slate-100 antialiased">
    <div class="pointer-events-none fixed inset-0 overflow-hidden" aria-hidden="true">
        <div class="absolute -left-24 top-16 h-72 w-72 rounded-full bg-cyan-500/10 blur-3xl"></div>
        <div class="absolute -right-24 top-1/3 h-80 w-80 rounded-full bg-violet-500/10 blur-3xl"></div>
    </div>

    @auth
        <header class="relative border-b border-white/10 bg-slate-950/80 backdrop-blur">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-5 py-4 sm:px-8">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 font-semibold tracking-tight">
                    <span class="grid h-9 w-9 place-items-center rounded-xl bg-cyan-400 text-sm font-black text-slate-950">EP</span>
                    <span>EduPulse <span class="text-cyan-300">AI</span></span>
                </a>

                <div class="flex items-center gap-4">
                    <div class="hidden text-right sm:block">
                        <p class="text-sm font-medium">{{ auth()->user()->full_name }}</p>
                        <p class="text-xs uppercase tracking-wider text-slate-400">{{ auth()->user()->role->value }}</p>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="rounded-xl border border-white/10 px-4 py-2 text-sm text-slate-300 transition hover:border-cyan-300/50 hover:text-white" type="submit">
                            Log out
                        </button>
                    </form>
                </div>
            </div>
        </header>
    @endauth

    <main class="relative">
        @yield('content')
    </main>
</body>
</html>
