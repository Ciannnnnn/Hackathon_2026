@extends('layouts.app', ['title' => 'EduPulse AI | Early support. Personal learning.'])

@section('content')
    <nav class="mx-auto flex max-w-7xl items-center justify-between px-5 py-6 sm:px-8" aria-label="Main navigation">
        <a href="{{ url('/') }}" class="flex items-center gap-3 font-semibold tracking-tight">
            <span class="grid h-10 w-10 place-items-center rounded-xl bg-cyan-400 text-sm font-black text-slate-950">EP</span>
            <span>EduPulse <span class="text-cyan-300">AI</span></span>
        </a>
        <div class="hidden items-center gap-8 text-sm text-slate-400 md:flex">
            <a href="#features" class="transition hover:text-white">Features</a>
            <a href="#how-it-works" class="transition hover:text-white">How it works</a>
            <a href="#for-educators" class="transition hover:text-white">For educators</a>
        </div>
        <a href="{{ route('login') }}" class="rounded-xl border border-white/15 bg-white/[0.06] px-4 py-2.5 text-sm font-semibold text-white transition hover:border-cyan-300/50 hover:bg-white/10">
            Sign in
        </a>
    </nav>

    <section class="mx-auto grid max-w-7xl items-center gap-14 px-5 pb-24 pt-16 sm:px-8 lg:grid-cols-[1.05fr_.95fr] lg:pb-32 lg:pt-24">
        <div>
            <div class="inline-flex items-center gap-2 rounded-full border border-cyan-300/20 bg-cyan-300/10 px-3 py-1.5 text-xs font-semibold text-cyan-200">
                <span class="h-1.5 w-1.5 rounded-full bg-cyan-300"></span>
                AI-powered academic support
            </div>
            <h1 class="mt-7 max-w-3xl text-5xl font-semibold leading-[1.05] tracking-[-0.04em] text-white sm:text-6xl lg:text-7xl">
                Detect learning difficulties early.
                <span class="bg-gradient-to-r from-cyan-300 to-indigo-300 bg-clip-text text-transparent">Personalize learning automatically.</span>
            </h1>
            <p class="mt-7 max-w-2xl text-lg leading-8 text-slate-400">
                EduPulse AI transforms academic data into personalized learning support using machine learning and generative AI.
            </p>
            <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                <a href="{{ route('login') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-cyan-400 px-6 py-3.5 font-semibold text-slate-950 shadow-xl shadow-cyan-950/30 transition hover:bg-cyan-300">
                    Get started
                    <x-icon name="arrow" class="h-4 w-4" />
                </a>
                <a href="#how-it-works" class="inline-flex items-center justify-center rounded-xl border border-white/15 px-6 py-3.5 font-semibold text-white transition hover:bg-white/[0.06]">
                    View the workflow
                </a>
            </div>
            <div class="mt-10 flex flex-wrap gap-x-8 gap-y-3 text-sm text-slate-500">
                <span class="flex items-center gap-2"><x-icon name="check" class="h-4 w-4 text-emerald-400" /> Academic—not medical—insights</span>
                <span class="flex items-center gap-2"><x-icon name="check" class="h-4 w-4 text-emerald-400" /> Role-secured access</span>
            </div>
        </div>

        <div class="relative">
            <div class="absolute -inset-8 rounded-full bg-cyan-400/10 blur-3xl"></div>
            <div class="relative overflow-hidden rounded-3xl border border-white/10 bg-slate-900/90 p-4 shadow-2xl shadow-black/40 backdrop-blur sm:p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-cyan-300">Class pulse</p>
                        <p class="mt-1 text-lg font-semibold text-white">Database Management</p>
                    </div>
                    <span class="rounded-full bg-emerald-400/10 px-3 py-1 text-xs font-semibold text-emerald-300">Live overview</span>
                </div>

                <div class="mt-6 grid grid-cols-3 gap-3">
                    <div class="rounded-2xl bg-white/[0.06] p-4">
                        <p class="text-xs text-slate-500">Students</p><p class="mt-2 text-2xl font-semibold">10</p>
                    </div>
                    <div class="rounded-2xl bg-white/[0.06] p-4">
                        <p class="text-xs text-slate-500">Need support</p><p class="mt-2 text-2xl font-semibold text-amber-300">6</p>
                    </div>
                    <div class="rounded-2xl bg-white/[0.06] p-4">
                        <p class="text-xs text-slate-500">Class average</p><p class="mt-2 text-2xl font-semibold">80%</p>
                    </div>
                </div>

                <div class="mt-4 rounded-2xl bg-white/[0.04] p-5">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-medium text-slate-300">Performance movement</p>
                        <p class="text-xs text-slate-500">Last 6 weeks</p>
                    </div>
                    <svg class="mt-5 h-36 w-full" viewBox="0 0 500 145" role="img" aria-label="Illustrative performance trend">
                        <defs>
                            <linearGradient id="line-fill" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0" stop-color="#22d3ee" stop-opacity=".3" />
                                <stop offset="1" stop-color="#22d3ee" stop-opacity="0" />
                            </linearGradient>
                        </defs>
                        <path d="M0 120H500M0 80H500M0 40H500" stroke="#334155" stroke-width="1" stroke-dasharray="4 6" />
                        <path d="M0 108 C65 95 90 62 145 72 S230 112 285 75 S370 38 430 48 S472 30 500 22 L500 145 L0 145Z" fill="url(#line-fill)" />
                        <path d="M0 108 C65 95 90 62 145 72 S230 112 285 75 S370 38 430 48 S472 30 500 22" fill="none" stroke="#22d3ee" stroke-width="4" stroke-linecap="round" />
                    </svg>
                </div>

                <div class="mt-4 flex items-center gap-4 rounded-2xl border border-rose-400/15 bg-rose-400/[0.07] p-4">
                    <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-rose-400/10 text-rose-300"><x-icon name="sparkles" /></span>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2"><p class="font-semibold text-white">Alex Santos</p><span class="rounded-full bg-rose-400/15 px-2 py-0.5 text-[10px] font-bold text-rose-300">HIGH</span></div>
                        <p class="mt-1 truncate text-xs text-slate-400">Priority topic: Database Normalization</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="features" class="border-y border-white/10 bg-white/[0.025] py-24">
        <div class="mx-auto max-w-7xl px-5 sm:px-8">
            <div class="max-w-2xl">
                <p class="text-sm font-semibold uppercase tracking-[0.22em] text-cyan-300">One connected support loop</p>
                <h2 class="mt-4 text-3xl font-semibold tracking-tight text-white sm:text-4xl">Turn signals into meaningful learning action.</h2>
            </div>
            <div class="mt-12 grid gap-5 md:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    ['icon' => 'trend', 'title' => 'Early support detection', 'copy' => 'Combine attendance, assessments, submissions, activity, and trends into an academic support level.'],
                    ['icon' => 'calendar', 'title' => 'Personalized learning', 'copy' => 'Generate focused seven-day plans that respond to weak topics and recent performance.'],
                    ['icon' => 'bot', 'title' => 'Grounded AI Tutor', 'copy' => 'Help students learn from instructor-provided materials with clear explanations and examples.'],
                    ['icon' => 'chart', 'title' => 'Teacher analytics', 'copy' => 'Prioritize the right learners quickly through class trends, distributions, and actionable context.'],
                ] as $feature)
                    <article class="rounded-2xl border border-white/10 bg-white/[0.04] p-6 transition hover:-translate-y-1 hover:border-cyan-300/25 hover:bg-white/[0.06]">
                        <span class="grid h-11 w-11 place-items-center rounded-xl bg-cyan-300/10 text-cyan-300"><x-icon :name="$feature['icon']" /></span>
                        <h3 class="mt-6 font-semibold text-white">{{ $feature['title'] }}</h3>
                        <p class="mt-3 text-sm leading-6 text-slate-400">{{ $feature['copy'] }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section id="how-it-works" class="mx-auto max-w-7xl px-5 py-24 sm:px-8">
        <div class="grid gap-14 lg:grid-cols-[.8fr_1.2fr]">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.22em] text-indigo-300">How it works</p>
                <h2 class="mt-4 text-3xl font-semibold tracking-tight text-white sm:text-4xl">From academic data to guided progress.</h2>
                <p class="mt-5 leading-7 text-slate-400">Machine learning identifies support needs. Generative AI explains the signals and creates practical next steps—always framed as academic guidance.</p>
            </div>
            <ol class="grid gap-4 sm:grid-cols-2">
                @foreach ([
                    ['01', 'Observe', 'Bring together attendance, quizzes, assignments, submissions, and learning activity.'],
                    ['02', 'Prioritize', 'Classify support needs as low, moderate, or high with confidence and context.'],
                    ['03', 'Personalize', 'Create recommendations and study tasks around actual weak topics.'],
                    ['04', 'Reinforce', 'Use grounded tutoring and targeted quizzes to close the loop.'],
                ] as [$number, $title, $copy])
                    <li class="rounded-2xl border border-white/10 bg-slate-900/70 p-6">
                        <span class="text-xs font-bold tracking-[0.2em] text-cyan-300">{{ $number }}</span>
                        <h3 class="mt-4 text-lg font-semibold text-white">{{ $title }}</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-400">{{ $copy }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    <section id="for-educators" class="mx-auto max-w-7xl px-5 pb-24 sm:px-8">
        <div class="overflow-hidden rounded-3xl border border-cyan-300/20 bg-gradient-to-br from-cyan-400/15 via-indigo-400/10 to-transparent px-6 py-14 text-center sm:px-12">
            <p class="text-sm font-semibold uppercase tracking-[0.22em] text-cyan-200">Build a clearer path forward</p>
            <h2 class="mx-auto mt-4 max-w-3xl text-3xl font-semibold tracking-tight text-white sm:text-4xl">Help every learner get the right support at the right time.</h2>
            <p class="mx-auto mt-5 max-w-2xl leading-7 text-slate-300">See the demo dashboards and experience the complete EduPulse support workflow.</p>
            <a href="{{ route('login') }}" class="mt-8 inline-flex items-center gap-2 rounded-xl bg-white px-6 py-3 font-semibold text-slate-950 transition hover:bg-cyan-50">
                Open EduPulse AI <x-icon name="arrow" class="h-4 w-4" />
            </a>
        </div>
    </section>

    <footer class="border-t border-white/10 py-8">
        <div class="mx-auto flex max-w-7xl flex-col gap-3 px-5 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between sm:px-8">
            <p>© {{ now()->year }} EduPulse AI. Built for better learning support.</p>
            <p>Academic insights, never medical diagnosis.</p>
        </div>
    </footer>
@endsection
