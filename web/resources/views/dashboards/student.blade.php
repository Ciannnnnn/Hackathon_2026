@extends('layouts.dashboard', ['title' => 'Student Dashboard | EduPulse AI'])

@section('content')
    <div class="flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
        <div>
            <p class="text-sm font-semibold text-cyan-600">Your learning pulse</p>
            <h1 class="mt-1 text-3xl font-semibold tracking-tight text-slate-950">Keep moving forward, {{ auth()->user()->first_name }}</h1>
            <p class="mt-2 text-sm text-slate-500">A focused view of your progress, priorities, and next learning actions.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @forelse ($subjects as $subject)
                <span class="rounded-full bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 shadow-sm ring-1 ring-slate-200">{{ $subject['code'] }}</span>
            @empty
                <span class="rounded-full bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700 ring-1 ring-amber-200">No subjects linked</span>
            @endforelse
        </div>
    </div>

    <section class="mt-7 grid gap-6 xl:grid-cols-[1.45fr_1fr]">
        <article class="overflow-hidden rounded-2xl bg-gradient-to-br from-slate-950 via-slate-900 to-indigo-950 p-6 text-white shadow-xl shadow-slate-300/30 sm:p-8">
            <div class="flex flex-col gap-8 sm:flex-row sm:items-center sm:justify-between">
                <div class="max-w-xl">
                    <div class="flex items-center gap-3">
                        <x-support-badge :level="$status['support_level']" />
                        <span class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Academic support level</span>
                    </div>
                    <h2 class="mt-5 text-2xl font-semibold tracking-tight">{{ $status['label'] }}</h2>
                    <p class="mt-3 text-sm leading-6 text-slate-400">{{ $status['summary'] }}</p>
                </div>
                <div class="relative grid h-32 w-32 shrink-0 place-items-center rounded-full" style="background: conic-gradient(#22d3ee {{ min(100, $status['overall_score']) }}%, rgba(255,255,255,.08) 0)">
                    <div class="grid h-24 w-24 place-items-center rounded-full bg-slate-950 text-center">
                        <div><p class="text-3xl font-semibold">{{ number_format($status['overall_score'], 0) }}</p><p class="text-[10px] uppercase tracking-wider text-slate-500">Overall</p></div>
                    </div>
                </div>
            </div>
            @if ($status['weak_topics']->isNotEmpty())
                <div class="mt-7 flex flex-wrap items-center gap-2 border-t border-white/10 pt-5">
                    <span class="mr-1 text-xs font-medium text-slate-500">Focus next:</span>
                    @foreach ($status['weak_topics'] as $topic)
                        <span class="rounded-full bg-white/[0.07] px-3 py-1 text-xs text-slate-300 ring-1 ring-white/10">{{ $topic }}</span>
                    @endforeach
                </div>
            @endif
        </article>

        <article class="dashboard-panel p-6">
            <div class="flex items-center justify-between">
                <div><h2 class="font-semibold text-slate-900">Quick actions</h2><p class="mt-1 text-xs text-slate-400">Tools built around your learning needs</p></div>
                <span class="grid h-10 w-10 place-items-center rounded-xl bg-cyan-50 text-cyan-600"><x-icon name="sparkles" /></span>
            </div>
            <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-1 2xl:grid-cols-2">
                <a href="#study-plan" class="group rounded-xl border border-slate-200 p-4 transition hover:border-cyan-200 hover:bg-cyan-50/50">
                    <span class="grid h-9 w-9 place-items-center rounded-lg bg-indigo-50 text-indigo-600"><x-icon name="calendar" class="h-4 w-4" /></span>
                    <p class="mt-3 text-sm font-semibold text-slate-800">Study plan</p><p class="mt-1 text-xs text-slate-400">Continue today's task</p>
                </a>
                <div class="cursor-not-allowed rounded-xl border border-slate-200 p-4 opacity-70">
                    <span class="grid h-9 w-9 place-items-center rounded-lg bg-cyan-50 text-cyan-600"><x-icon name="bot" class="h-4 w-4" /></span>
                    <p class="mt-3 text-sm font-semibold text-slate-800">AI Tutor</p><p class="mt-1 text-xs text-slate-400">Coming in Phase 10</p>
                </div>
            </div>
        </article>
    </section>

    <section class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Current academic metrics">
        <x-metric-card label="Attendance" :value="$current['attendance']" suffix="%" detail="Latest recorded attendance rate" icon="calendar" tone="emerald" />
        <x-metric-card label="Quiz average" :value="$current['quiz_average']" suffix="%" detail="Latest quiz performance snapshot" icon="quiz" tone="cyan" />
        <x-metric-card label="Assignment average" :value="$current['assignment_average']" suffix="%" detail="Latest assignment performance" icon="book" tone="indigo" />
        <x-metric-card label="Performance trend" :value="($current['trend'] > 0 ? '+' : '').$current['trend']" suffix=" pts" detail="Change from the comparison period" icon="trend" :tone="$current['trend'] < 0 ? 'rose' : 'emerald'" />
    </section>

    <section class="mt-6 grid gap-6 xl:grid-cols-[1.45fr_1fr]">
        <article class="dashboard-panel p-5 sm:p-6">
            <div class="flex items-start justify-between gap-4">
                <div><h2 class="font-semibold text-slate-900">Your progress</h2><p class="mt-1 text-xs text-slate-400">Quiz and assignment performance over time</p></div>
                <span class="rounded-lg bg-indigo-50 px-2.5 py-1 text-[11px] font-semibold text-indigo-700">Scores out of 100</span>
            </div>
            <div data-chart-container class="mt-5 h-72">
                <canvas
                    data-chart-config="{{ json_encode([
                        'type' => 'line',
                        'labels' => $progress_chart['labels'],
                        'datasets' => [
                            ['label' => 'Quiz score', 'data' => $progress_chart['quiz'], 'color' => 'cyan'],
                            ['label' => 'Assignment score', 'data' => $progress_chart['assignment'], 'color' => 'indigo'],
                        ],
                    ]) }}"
                    aria-label="Student progress chart"
                ></canvas>
            </div>
        </article>

        <article class="dashboard-panel p-5 sm:p-6">
            <div class="flex items-center justify-between">
                <div><h2 class="font-semibold text-slate-900">Recent quizzes</h2><p class="mt-1 text-xs text-slate-400">Your latest assessment results</p></div>
                <span class="grid h-10 w-10 place-items-center rounded-xl bg-amber-50 text-amber-600"><x-icon name="quiz" /></span>
            </div>
            <div class="mt-5 space-y-1">
                @forelse ($recent_quizzes as $quiz)
                    @php($percentage = $quiz->max_score > 0 ? round(($quiz->score / $quiz->max_score) * 100) : 0)
                    <div class="flex items-center gap-4 rounded-xl px-2 py-3 transition hover:bg-slate-50">
                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl {{ $percentage >= 75 ? 'bg-emerald-50 text-emerald-600' : 'bg-amber-50 text-amber-600' }} text-xs font-bold">{{ $percentage }}%</span>
                        <div class="min-w-0 flex-1"><p class="truncate text-sm font-medium text-slate-700">{{ $quiz->topic }}</p><p class="mt-0.5 text-xs text-slate-400">{{ $quiz->subject_code }} · {{ \Illuminate\Support\Carbon::parse($quiz->taken_at)->format('M d') }}</p></div>
                    </div>
                @empty
                    <x-empty-state title="No quiz attempts yet" message="Your recent quiz results will appear here after an assessment." />
                @endforelse
            </div>
        </article>
    </section>

    <section id="study-plan" class="dashboard-panel mt-6 p-5 sm:p-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div><p class="text-xs font-bold uppercase tracking-[0.16em] text-indigo-600">Personalized learning</p><h2 class="mt-1 font-semibold text-slate-900">{{ $study_plan['title'] ?? 'Your 7-day study plan' }}</h2></div>
            <span class="rounded-full bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 ring-1 ring-indigo-100">AI-guided plan</span>
        </div>

        @if ($study_plan['items']->isNotEmpty())
            <div class="mt-6 grid gap-3 md:grid-cols-2 xl:grid-cols-4 2xl:grid-cols-7">
                @foreach ($study_plan['items'] as $item)
                    <article class="relative rounded-xl border p-4 {{ $item->is_completed ? 'border-emerald-200 bg-emerald-50/60' : 'border-slate-200 bg-white' }}">
                        <div class="flex items-center justify-between"><span class="text-[10px] font-bold uppercase tracking-[0.14em] {{ $item->is_completed ? 'text-emerald-600' : 'text-indigo-600' }}">Day {{ $item->day_number }}</span>@if ($item->is_completed)<x-icon name="check" class="h-4 w-4 text-emerald-600" />@endif</div>
                        <p class="mt-3 text-sm font-semibold text-slate-800">{{ $item->topic }}</p>
                        <p class="mt-2 line-clamp-3 text-xs leading-5 text-slate-400">{{ $item->task }}</p>
                    </article>
                @endforeach
            </div>
        @else
            <div class="mt-5"><x-empty-state title="Your plan is being prepared" message="A personalized plan will appear after your next support analysis." /></div>
        @endif
    </section>
@endsection
