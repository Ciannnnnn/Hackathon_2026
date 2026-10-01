@extends('layouts.dashboard', ['title' => 'Teacher Dashboard | EduPulse AI'])

@section('content')
    <div class="flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
        <div>
            <p class="text-sm font-semibold text-cyan-600">Teacher overview</p>
            <h1 class="mt-1 text-3xl font-semibold tracking-tight text-slate-950">Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 18 ? 'afternoon' : 'evening') }}, {{ auth()->user()->first_name }}</h1>
            <p class="mt-2 text-sm text-slate-500">Here is the latest academic pulse across your active classes.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @forelse ($subjects as $subject)
                <span class="rounded-full bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 shadow-sm ring-1 ring-slate-200">{{ $subject['code'] }}</span>
            @empty
                <span class="rounded-full bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700 ring-1 ring-amber-200">No active subjects</span>
            @endforelse
        </div>
    </div>

    <section class="mt-7 grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Class metrics">
        <x-metric-card label="Total students" :value="$metrics['total_students']" detail="Unique learners across active classes" icon="users" tone="indigo" />
        <x-metric-card label="Need support" :value="$metrics['support_needed']" detail="Moderate or high academic support" icon="sparkles" tone="rose" />
        <x-metric-card label="Class performance" :value="$metrics['average_performance']" suffix="%" detail="Combined quiz and assignment average" icon="trend" tone="cyan" />
        <x-metric-card label="Attendance" :value="$metrics['average_attendance']" suffix="%" detail="Average of latest performance records" icon="calendar" tone="emerald" />
    </section>

    <section class="mt-6 grid gap-6 xl:grid-cols-[1.65fr_1fr]">
        <article class="dashboard-panel p-5 sm:p-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="font-semibold text-slate-900">Class performance trend</h2>
                    <p class="mt-1 text-xs text-slate-400">Average assessment movement by snapshot</p>
                </div>
                <span class="rounded-lg bg-cyan-50 px-2.5 py-1 text-[11px] font-semibold text-cyan-700">Scores out of 100</span>
            </div>
            <div data-chart-container class="mt-5 h-72">
                <canvas
                    data-chart-config="{{ json_encode([
                        'type' => 'line',
                        'labels' => $performance_chart['labels'],
                        'datasets' => [
                            ['label' => 'Quiz average', 'data' => $performance_chart['quiz'], 'color' => 'cyan'],
                            ['label' => 'Assignment average', 'data' => $performance_chart['assignment'], 'color' => 'indigo'],
                        ],
                    ]) }}"
                    aria-label="Class performance trend chart"
                ></canvas>
            </div>
        </article>

        <article class="dashboard-panel p-5 sm:p-6">
            <div>
                <h2 class="font-semibold text-slate-900">Support distribution</h2>
                <p class="mt-1 text-xs text-slate-400">Latest level for each learner</p>
            </div>
            <div data-chart-container class="mt-3 h-72">
                <canvas
                    data-chart-config="{{ json_encode([
                        'type' => 'doughnut',
                        'labels' => $support_chart['labels'],
                        'datasets' => [['label' => 'Students', 'data' => $support_chart['values']]],
                    ]) }}"
                    aria-label="Academic support distribution chart"
                ></canvas>
            </div>
        </article>
    </section>

    <section class="dashboard-panel mt-6 overflow-hidden">
        <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <div>
                <h2 class="font-semibold text-slate-900">Student academic pulse</h2>
                <p class="mt-1 text-xs text-slate-400">Prioritized by latest academic support level</p>
            </div>
            <span class="text-xs font-medium text-slate-400">{{ $students->count() }} students</span>
        </div>

        @if ($students->isNotEmpty())
            <div class="overflow-x-auto">
                <table class="w-full min-w-[980px] text-left">
                    <thead class="bg-slate-50/80 text-[10px] font-bold uppercase tracking-[0.12em] text-slate-400">
                        <tr>
                            <th class="px-6 py-3.5">Student</th>
                            <th class="px-4 py-3.5">Attendance</th>
                            <th class="px-4 py-3.5">Quiz avg.</th>
                            <th class="px-4 py-3.5">Assignment avg.</th>
                            <th class="px-4 py-3.5">Missing</th>
                            <th class="px-4 py-3.5">Trend</th>
                            <th class="px-4 py-3.5">Support level</th>
                            <th class="px-6 py-3.5"><span class="sr-only">Open</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($students as $student)
                            <tr class="group transition hover:bg-cyan-50/30">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-gradient-to-br from-slate-100 to-slate-200 text-xs font-bold text-slate-600">
                                            {{ collect(explode(' ', $student['name']))->map(fn ($part) => substr($part, 0, 1))->take(2)->join('') }}
                                        </span>
                                        <div><p class="text-sm font-semibold text-slate-800">{{ $student['name'] }}</p><p class="mt-0.5 text-xs text-slate-400">{{ $student['student_number'] }}</p></div>
                                    </div>
                                </td>
                                @foreach (['attendance', 'quiz_average', 'assignment_average'] as $metric)
                                    <td class="px-4 py-4 text-sm font-medium text-slate-600">
                                        {{ $student[$metric] !== null ? number_format($student[$metric], 0).'%' : '—' }}
                                    </td>
                                @endforeach
                                <td class="px-4 py-4 text-sm font-medium {{ $student['missing_submissions'] > 0 ? 'text-rose-600' : 'text-slate-500' }}">{{ $student['missing_submissions'] }}</td>
                                <td class="px-4 py-4">
                                    @php($trendTone = $student['trend_label'] === 'Improving' ? 'text-emerald-600 bg-emerald-50' : ($student['trend_label'] === 'Declining' ? 'text-rose-600 bg-rose-50' : 'text-slate-600 bg-slate-100'))
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $trendTone }}">{{ $student['trend_label'] }}</span>
                                </td>
                                <td class="px-4 py-4"><x-support-badge :level="$student['support_level']" /></td>
                                <td class="px-6 py-4 text-right"><a href="{{ route('teacher.students.show', $student['id']) }}" class="inline-grid h-8 w-8 place-items-center rounded-lg text-slate-400 transition group-hover:bg-white group-hover:text-cyan-600 group-hover:shadow-sm" aria-label="View {{ $student['name'] }} analysis"><x-icon name="arrow" class="h-4 w-4" /></a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="p-6"><x-empty-state title="No students yet" message="Enroll students and add performance records to populate this academic pulse." /></div>
        @endif
    </section>

    <section class="mt-6 grid gap-6 lg:grid-cols-2">
        <article class="dashboard-panel p-5 sm:p-6">
            <div class="flex items-center justify-between">
                <div><h2 class="font-semibold text-slate-900">Recent academic activity</h2><p class="mt-1 text-xs text-slate-400">Latest performance snapshots</p></div>
                <span class="grid h-10 w-10 place-items-center rounded-xl bg-indigo-50 text-indigo-600"><x-icon name="trend" /></span>
            </div>
            <div class="mt-5 space-y-1">
                @forelse ($recent_activity as $activity)
                    <div class="flex items-center gap-4 rounded-xl px-2 py-3 transition hover:bg-slate-50">
                        <span class="h-2.5 w-2.5 shrink-0 rounded-full bg-cyan-400 ring-4 ring-cyan-50"></span>
                        <div class="min-w-0 flex-1"><p class="truncate text-sm font-medium text-slate-700">{{ $activity['student'] }}</p><p class="mt-0.5 text-xs text-slate-400">{{ $activity['subject'] }} · {{ $activity['date'] }}</p></div>
                        <span class="text-sm font-semibold text-slate-700">{{ number_format($activity['quiz_average'], 0) }}%</span>
                    </div>
                @empty
                    <x-empty-state title="No recent activity" message="Performance snapshots will appear here after assessments are recorded." />
                @endforelse
            </div>
        </article>

        <article class="dashboard-panel p-5 sm:p-6">
            <div class="flex items-center justify-between">
                <div><h2 class="font-semibold text-slate-900">Frequent weak topics</h2><p class="mt-1 text-xs text-slate-400">Topics appearing in support analyses</p></div>
                <span class="grid h-10 w-10 place-items-center rounded-xl bg-amber-50 text-amber-600"><x-icon name="book" /></span>
            </div>
            <div class="mt-6 space-y-5">
                @forelse ($weak_topics as $topic)
                    @php($maxTopicCount = max(1, $weak_topics->max('count')))
                    <div>
                        <div class="flex items-center justify-between gap-4 text-sm"><span class="font-medium text-slate-700">{{ $topic['topic'] }}</span><span class="text-xs font-semibold text-slate-400">{{ $topic['count'] }} students</span></div>
                        <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-gradient-to-r from-amber-400 to-rose-400" style="width: {{ ($topic['count'] / $maxTopicCount) * 100 }}%"></div></div>
                    </div>
                @empty
                    <x-empty-state title="No weak topics identified" message="Topic frequency will populate as AI support analyses are created." />
                @endforelse
            </div>
        </article>
    </section>
@endsection
