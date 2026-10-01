@extends('layouts.dashboard', ['title' => $student->user->full_name.' Analysis | EduPulse AI'])

@section('content')
    <div class="flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
        <div>
            <a href="{{ route('teacher.students.index') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-cyan-700 transition hover:text-cyan-900">← Back to students</a>
            <div class="mt-4 flex items-center gap-4">
                <span class="grid h-14 w-14 shrink-0 place-items-center rounded-2xl bg-gradient-to-br from-cyan-200 to-indigo-200 text-lg font-bold text-indigo-800 shadow-sm">
                    {{ strtoupper(substr($student->user->first_name, 0, 1).substr($student->user->last_name, 0, 1)) }}
                </span>
                <div>
                    <h1 class="text-3xl font-semibold tracking-tight text-slate-950">{{ $student->user->full_name }}</h1>
                    <p class="mt-1 text-sm text-slate-500">{{ $student->student_number }} · {{ $student->grade_level }} · {{ $student->program ?? 'Program not set' }}</p>
                </div>
            </div>
        </div>
        <div class="flex flex-wrap gap-2">
            @foreach ($subjects as $subject)
                <a href="{{ route('teacher.students.show', ['student' => $student, 'subject' => $subject->id]) }}" class="rounded-full px-3.5 py-2 text-xs font-semibold ring-1 transition {{ $selected_subject->id === $subject->id ? 'bg-slate-950 text-white ring-slate-950' : 'bg-white text-slate-600 ring-slate-200 hover:ring-cyan-300 hover:text-cyan-700' }}">
                    {{ $subject->code }}
                </a>
            @endforeach
        </div>
    </div>

    <div class="mt-7 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Current subject</p>
            <p class="mt-1 font-semibold text-slate-800">{{ $selected_subject->code }} · {{ $selected_subject->title }}</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="text-xs text-slate-400">Latest snapshot {{ $latest?->snapshot_date?->format('M d, Y') ?? 'not recorded' }}</span>
            <x-support-badge :level="$analysis?->support_level?->value ?? 'UNASSESSED'" />
        </div>
    </div>

    @if ($errors->any())
        <div class="mt-6 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800" role="alert">
            <p class="font-semibold">The performance snapshot could not be saved.</p>
            <ul class="mt-2 list-disc space-y-1 pl-5 text-xs">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <section class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Latest student performance">
        <x-metric-card label="Attendance" :value="$latest ? number_format((float) $latest->attendance_rate, 0) : '—'" :suffix="$latest ? '%' : null" detail="Latest recorded attendance rate" icon="calendar" tone="emerald" />
        <x-metric-card label="Quiz average" :value="$latest ? number_format((float) $latest->quiz_average, 0) : '—'" :suffix="$latest ? '%' : null" detail="Average across recent quizzes" icon="quiz" tone="cyan" />
        <x-metric-card label="Assignment average" :value="$latest ? number_format((float) $latest->assignment_average, 0) : '—'" :suffix="$latest ? '%' : null" detail="Graded assignment performance" icon="book" tone="indigo" />
        <x-metric-card label="Activity score" :value="$latest ? number_format((float) $latest->activity_score, 0) : '—'" :suffix="$latest ? '%' : null" detail="Participation in learning activities" icon="trend" tone="amber" />
    </section>

    <section class="mt-6 grid gap-6 xl:grid-cols-[1.55fr_1fr]">
        <article class="dashboard-panel p-5 sm:p-6">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="font-semibold text-slate-900">Performance history</h2>
                    <p class="mt-1 text-xs text-slate-400">Attendance, quiz, and assignment movement over time</p>
                </div>
                <span class="rounded-lg bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-500">{{ $records->count() }} snapshots</span>
            </div>
            <div data-chart-container class="mt-5 h-72">
                <canvas data-chart-config="{{ json_encode([
                    'type' => 'line',
                    'labels' => $performance_chart['labels'],
                    'datasets' => [
                        ['label' => 'Attendance', 'data' => $performance_chart['attendance'], 'color' => 'emerald'],
                        ['label' => 'Quiz average', 'data' => $performance_chart['quiz'], 'color' => 'cyan'],
                        ['label' => 'Assignment average', 'data' => $performance_chart['assignment'], 'color' => 'indigo'],
                    ],
                ]) }}" aria-label="Student performance history chart"></canvas>
            </div>
        </article>

        <article class="dashboard-panel overflow-hidden">
            <div class="bg-gradient-to-br from-slate-950 to-indigo-950 p-6 text-white">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-cyan-300">Academic support level</p>
                        <h2 class="mt-2 text-2xl font-semibold">{{ $analysis?->support_level?->value ?? 'UNASSESSED' }}</h2>
                    </div>
                    <span class="grid h-11 w-11 place-items-center rounded-xl bg-white/10 text-cyan-300"><x-icon name="sparkles" /></span>
                </div>
                @if ($analysis)
                    <p class="mt-4 text-sm leading-6 text-slate-300">{{ $analysis->ai_summary }}</p>
                    <div class="mt-4 flex flex-wrap gap-2 text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                        <span class="rounded-full bg-white/10 px-2.5 py-1">{{ str_replace('_', ' ', $analysis->analysis_source) }}</span>
                        @if ($analysis->confidence !== null)<span class="rounded-full bg-white/10 px-2.5 py-1">{{ number_format((float) $analysis->confidence * 100, 0) }}% confidence</span>@endif
                    </div>
                @else
                    <p class="mt-4 text-sm leading-6 text-slate-300">Add a performance snapshot to create the first provisional academic support assessment.</p>
                @endif
            </div>
            <div class="p-6">
                <h3 class="text-sm font-semibold text-slate-800">Weak topics</h3>
                <div class="mt-3 flex flex-wrap gap-2">
                    @forelse ($analysis?->weak_topics ?? [] as $topic)
                        <span class="rounded-full bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700 ring-1 ring-amber-200">{{ $topic }}</span>
                    @empty
                        <p class="text-xs leading-5 text-slate-400">No specific weak topics have been identified.</p>
                    @endforelse
                </div>
            </div>
        </article>
    </section>

    <section class="mt-6 grid gap-6 xl:grid-cols-2">
        <article class="dashboard-panel p-5 sm:p-6">
            <div class="flex items-center justify-between gap-4">
                <div><h2 class="font-semibold text-slate-900">Recommended actions</h2><p class="mt-1 text-xs text-slate-400">Current teacher and generated recommendations</p></div>
                <span class="grid h-10 w-10 place-items-center rounded-xl bg-rose-50 text-rose-600"><x-icon name="sparkles" /></span>
            </div>
            <div class="mt-5 space-y-3">
                @forelse ($recommendations as $recommendation)
                    <div class="flex gap-3 rounded-xl border border-slate-100 bg-slate-50/70 p-4">
                        <span class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full {{ $recommendation->priority === 'high' ? 'bg-rose-500' : ($recommendation->priority === 'medium' ? 'bg-amber-400' : 'bg-emerald-400') }}"></span>
                        <div><p class="text-sm leading-6 text-slate-700">{{ $recommendation->recommendation_text }}</p><p class="mt-1 text-[10px] font-semibold uppercase tracking-wider text-slate-400">{{ $recommendation->priority }} priority · {{ str_replace('_', ' ', $recommendation->status) }}</p></div>
                    </div>
                @empty
                    <x-empty-state title="No recommendations yet" message="Personalized actions will appear here as support analysis is expanded in later phases." />
                @endforelse
            </div>
        </article>

        <article class="dashboard-panel p-5 sm:p-6">
            <div class="flex items-center justify-between gap-4">
                <div><h2 class="font-semibold text-slate-900">Personalized learning plan</h2><p class="mt-1 text-xs text-slate-400">Active plan for this subject</p></div>
                <span class="grid h-10 w-10 place-items-center rounded-xl bg-indigo-50 text-indigo-600"><x-icon name="calendar" /></span>
            </div>
            @if ($study_plan['plan'])
                <p class="mt-5 text-sm font-semibold text-slate-800">{{ $study_plan['plan']->title }}</p>
                <div class="mt-3 max-h-72 space-y-2 overflow-y-auto pr-1">
                    @foreach ($study_plan['items'] as $item)
                        <div class="flex gap-3 rounded-xl bg-slate-50 p-3.5">
                            <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-white text-xs font-bold text-indigo-600 shadow-sm ring-1 ring-slate-200">{{ $item->day_number }}</span>
                            <div><p class="text-sm font-medium text-slate-700">{{ $item->topic }}</p><p class="mt-1 text-xs leading-5 text-slate-400">{{ $item->task }}</p></div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="mt-5"><x-empty-state title="No active plan" message="Personalized seven-day plans will be generated in the AI study-plan phase." /></div>
            @endif
        </article>
    </section>

    <section class="dashboard-panel mt-6 overflow-hidden">
        <div class="border-b border-slate-100 px-5 py-5 sm:px-6">
            <h2 class="font-semibold text-slate-900">Record performance snapshot</h2>
            <p class="mt-1 text-xs text-slate-400">Saving the same subject and date updates that snapshot. A provisional rules-based support level is calculated until Phase 6 ML integration.</p>
        </div>
        <form method="POST" action="{{ route('teacher.students.performance.store', $student) }}" class="p-5 sm:p-6">
            @csrf
            <input type="hidden" name="subject_id" value="{{ $selected_subject->id }}" />
            <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
                <label class="block">
                    <span class="text-xs font-semibold text-slate-600">Snapshot date</span>
                    <input type="date" name="snapshot_date" max="{{ now()->toDateString() }}" value="{{ old('snapshot_date', now()->toDateString()) }}" required class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-3 text-sm outline-none focus:border-cyan-400 focus:bg-white focus:ring-4 focus:ring-cyan-100" />
                </label>
                @foreach ([
                    'attendance_rate' => ['Attendance rate', $latest?->attendance_rate],
                    'quiz_average' => ['Quiz average', $latest?->quiz_average],
                    'assignment_average' => ['Assignment average', $latest?->assignment_average],
                    'activity_score' => ['Activity score', $latest?->activity_score],
                ] as $name => [$label, $default])
                    <label class="block">
                        <span class="text-xs font-semibold text-slate-600">{{ $label }} (%)</span>
                        <input type="number" name="{{ $name }}" min="0" max="100" step="0.01" value="{{ old($name, $default) }}" required class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-3 text-sm outline-none focus:border-cyan-400 focus:bg-white focus:ring-4 focus:ring-cyan-100" />
                    </label>
                @endforeach
                <label class="block">
                    <span class="text-xs font-semibold text-slate-600">Late submissions</span>
                    <input type="number" name="late_submissions" min="0" max="100" value="{{ old('late_submissions', $latest?->late_submissions ?? 0) }}" required class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-3 text-sm outline-none focus:border-cyan-400 focus:bg-white focus:ring-4 focus:ring-cyan-100" />
                </label>
                <label class="block">
                    <span class="text-xs font-semibold text-slate-600">Missing submissions</span>
                    <input type="number" name="missing_submissions" min="0" max="100" value="{{ old('missing_submissions', $latest?->missing_submissions ?? 0) }}" required class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-3 text-sm outline-none focus:border-cyan-400 focus:bg-white focus:ring-4 focus:ring-cyan-100" />
                </label>
                <label class="block">
                    <span class="text-xs font-semibold text-slate-600">Performance trend</span>
                    <input type="number" name="performance_trend" min="-100" max="100" step="0.01" value="{{ old('performance_trend', $latest?->performance_trend ?? 0) }}" required class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-3 text-sm outline-none focus:border-cyan-400 focus:bg-white focus:ring-4 focus:ring-cyan-100" />
                    <span class="mt-1 block text-[10px] text-slate-400">Negative means declining.</span>
                </label>
                <label class="block sm:col-span-2 xl:col-span-4">
                    <span class="text-xs font-semibold text-slate-600">Weak topics <span class="font-normal text-slate-400">(comma-separated)</span></span>
                    <input name="weak_topics" maxlength="500" value="{{ old('weak_topics', implode(', ', $analysis?->weak_topics ?? [])) }}" placeholder="Database Normalization, SQL Joins" class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-3 text-sm outline-none placeholder:text-slate-400 focus:border-cyan-400 focus:bg-white focus:ring-4 focus:ring-cyan-100" />
                </label>
            </div>
            <div class="mt-6 flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-5">
                <p class="max-w-xl text-xs leading-5 text-slate-400">This assessment describes academic support needs only. It is not a medical, behavioral, or psychological diagnosis.</p>
                <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-cyan-600 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-cyan-600/20 transition hover:bg-cyan-700"><x-icon name="check" class="h-4 w-4" /> Save snapshot</button>
            </div>
        </form>
    </section>

    <section class="dashboard-panel mt-6 overflow-hidden">
        <div class="border-b border-slate-100 px-5 py-5 sm:px-6">
            <h2 class="font-semibold text-slate-900">Snapshot history</h2>
            <p class="mt-1 text-xs text-slate-400">Recorded academic indicators for {{ $selected_subject->code }}</p>
        </div>
        @if ($records->isNotEmpty())
            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px] text-left">
                    <thead class="bg-slate-50/80 text-[10px] font-bold uppercase tracking-wider text-slate-400"><tr><th class="px-6 py-3.5">Date</th><th class="px-4 py-3.5">Attendance</th><th class="px-4 py-3.5">Quiz</th><th class="px-4 py-3.5">Assignment</th><th class="px-4 py-3.5">Late</th><th class="px-4 py-3.5">Missing</th><th class="px-4 py-3.5">Activity</th><th class="px-6 py-3.5">Support</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($records as $record)
                            <tr><td class="px-6 py-4 text-sm font-semibold text-slate-700">{{ $record->snapshot_date->format('M d, Y') }}</td><td class="px-4 py-4 text-sm text-slate-600">{{ number_format((float) $record->attendance_rate, 0) }}%</td><td class="px-4 py-4 text-sm text-slate-600">{{ number_format((float) $record->quiz_average, 0) }}%</td><td class="px-4 py-4 text-sm text-slate-600">{{ number_format((float) $record->assignment_average, 0) }}%</td><td class="px-4 py-4 text-sm text-slate-600">{{ $record->late_submissions }}</td><td class="px-4 py-4 text-sm {{ $record->missing_submissions ? 'font-semibold text-rose-600' : 'text-slate-600' }}">{{ $record->missing_submissions }}</td><td class="px-4 py-4 text-sm text-slate-600">{{ number_format((float) $record->activity_score, 0) }}%</td><td class="px-6 py-4"><x-support-badge :level="$record->supportAnalysis?->support_level?->value ?? 'UNASSESSED'" /></td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="p-6"><x-empty-state title="No performance history" message="Use the form above to add this student's first performance snapshot." /></div>
        @endif
    </section>
@endsection
