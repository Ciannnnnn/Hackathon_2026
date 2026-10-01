@extends('layouts.dashboard', ['title' => 'Learning Analytics | EduPulse AI'])

@section('content')
    <div class="flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
        <div><p class="text-sm font-semibold text-cyan-600">Evidence-based overview</p><h1 class="mt-1 text-3xl font-semibold tracking-tight text-slate-950">Learning analytics</h1><p class="mt-2 text-sm text-slate-500">Track class progress, support needs, and grounded quiz performance by subject.</p></div>
        @if ($selectedSubject)<div class="flex flex-wrap gap-2" aria-label="Filter analytics by subject">@foreach ($subjects as $subject)<a href="{{ route('teacher.analytics', ['subject' => $subject->id]) }}" class="rounded-full px-3.5 py-2 text-xs font-semibold ring-1 transition {{ $selectedSubject->is($subject) ? 'bg-slate-950 text-white ring-slate-950' : 'bg-white text-slate-600 ring-slate-200 hover:ring-cyan-300 hover:text-cyan-700' }}">{{ $subject->code }}</a>@endforeach</div>@endif
    </div>

    @if ($selectedSubject)
        <section class="mt-7 grid gap-4 sm:grid-cols-2 xl:grid-cols-6" aria-label="Subject analytics summary">
            <x-metric-card label="Enrolled" :value="$metrics['enrolled']" detail="Active students" icon="users" tone="indigo" />
            <x-metric-card label="Assessed" :value="$metrics['assessed']" detail="With performance data" icon="check" tone="cyan" />
            <x-metric-card label="Need support" :value="$metrics['support_needed']" detail="Moderate or high" icon="sparkles" tone="rose" />
            <x-metric-card label="Class grade" :value="$metrics['average_grade']" suffix="%" detail="Latest average" icon="trend" tone="emerald" />
            <x-metric-card label="Attendance" :value="$metrics['average_attendance']" suffix="%" detail="Latest average" icon="calendar" tone="amber" />
            <x-metric-card label="Quiz attempts" :value="$metrics['quiz_attempts']" detail="AI quiz submissions" icon="quiz" tone="indigo" />
        </section>

        <section class="mt-6 grid gap-6 xl:grid-cols-[1.6fr_1fr]">
            <article class="dashboard-panel p-5 sm:p-6">
                <div><h2 class="font-semibold text-slate-900">Class performance over time</h2><p class="mt-1 text-xs text-slate-400">Average scores and attendance from recorded snapshots</p></div>
                <div data-chart-container class="mt-5 h-80"><canvas data-chart-config="{{ json_encode(['type' => 'line', 'labels' => $performanceChart['labels'], 'datasets' => [['label' => 'Quiz', 'data' => $performanceChart['quiz'], 'color' => 'cyan'], ['label' => 'Assignment', 'data' => $performanceChart['assignment'], 'color' => 'indigo'], ['label' => 'Attendance', 'data' => $performanceChart['attendance'], 'color' => 'emerald']]]) }}" aria-label="Line chart of class quiz, assignment, and attendance percentages"></canvas></div>
            </article>
            <article class="dashboard-panel p-5 sm:p-6">
                <div><h2 class="font-semibold text-slate-900">Support distribution</h2><p class="mt-1 text-xs text-slate-400">Latest academic support level per assessed student</p></div>
                <div data-chart-container class="mt-4 h-80"><canvas data-chart-config="{{ json_encode(['type' => 'doughnut', 'labels' => $supportChart['labels'], 'datasets' => [['label' => 'Students', 'data' => $supportChart['values']]]]) }}" aria-label="Doughnut chart of academic support levels"></canvas></div>
            </article>
        </section>

        <section class="mt-6 grid gap-6 xl:grid-cols-[1.4fr_1fr]">
            <article class="dashboard-panel p-5 sm:p-6">
                <div><h2 class="font-semibold text-slate-900">Generated quiz performance</h2><p class="mt-1 text-xs text-slate-400">Average student score for each grounded quiz</p></div>
                <div data-chart-container class="mt-5 h-72"><canvas data-chart-config="{{ json_encode(['type' => 'bar', 'labels' => $quizChart['labels'], 'datasets' => [['label' => 'Average score', 'data' => $quizChart['values'], 'color' => 'violet']]]) }}" aria-label="Bar chart of average generated quiz scores"></canvas></div>
                @if ($quizSeries->isNotEmpty())<div class="mt-4 flex flex-wrap gap-2">@foreach ($quizSeries as $quiz)<span class="rounded-lg bg-slate-50 px-2.5 py-1.5 text-[11px] text-slate-500">{{ $quiz['label'] }}: {{ $quiz['attempts'] }} {{ Str::plural('attempt', $quiz['attempts']) }}</span>@endforeach</div>@endif
            </article>
            <article class="dashboard-panel p-5 sm:p-6">
                <div><h2 class="font-semibold text-slate-900">Frequent weak topics</h2><p class="mt-1 text-xs text-slate-400">From each learner's latest support analysis</p></div>
                <div class="mt-5 space-y-4">
                    @forelse ($weakTopics as $topic)
                        <div><div class="flex justify-between gap-4 text-xs"><span class="font-medium text-slate-700">{{ $topic['topic'] }}</span><span class="text-slate-400">{{ $topic['count'] }} students</span></div><div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-gradient-to-r from-cyan-400 to-indigo-500" style="width: {{ min(100, ($topic['count'] / max(1, $metrics['assessed'])) * 100) }}%"></div></div></div>
                    @empty
                        <x-empty-state title="No weak topics yet" message="Weak-topic frequency appears after students receive support analyses." />
                    @endforelse
                </div>
            </article>
        </section>

        <section class="dashboard-panel mt-6 overflow-hidden">
            <div class="border-b border-slate-100 px-5 py-5 sm:px-6"><h2 class="font-semibold text-slate-900">Latest learner outcomes</h2><p class="mt-1 text-xs text-slate-400">Use this table to move from class trends to an individual intervention.</p></div>
            @if ($students->isNotEmpty())
                <div class="overflow-x-auto"><table class="w-full min-w-[760px] text-left"><thead class="bg-slate-50 text-[10px] font-bold uppercase tracking-wider text-slate-400"><tr><th class="px-6 py-3">Student</th><th class="px-4 py-3">Overall</th><th class="px-4 py-3">Attendance</th><th class="px-4 py-3">Trend</th><th class="px-4 py-3">Support</th><th class="px-6 py-3"><span class="sr-only">Open analysis</span></th></tr></thead><tbody class="divide-y divide-slate-100">@foreach ($students as $performance)<tr><td class="px-6 py-4 text-sm font-semibold text-slate-800">{{ $performance->student->user->full_name }}</td><td class="px-4 py-4 text-sm text-slate-600">{{ number_format($performance->overall_grade, 1) }}%</td><td class="px-4 py-4 text-sm text-slate-600">{{ number_format((float) $performance->attendance_rate, 1) }}%</td><td class="px-4 py-4 text-sm {{ (float) $performance->performance_trend < 0 ? 'text-rose-600' : 'text-emerald-600' }}">{{ (float) $performance->performance_trend > 0 ? '+' : '' }}{{ number_format((float) $performance->performance_trend, 1) }}</td><td class="px-4 py-4"><x-support-badge :level="$performance->supportAnalysis?->support_level?->value ?? 'UNASSESSED'" /></td><td class="px-6 py-4 text-right"><a href="{{ route('teacher.students.show', ['student' => $performance->student_id, 'subject' => $selectedSubject->id]) }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">View learner</a></td></tr>@endforeach</tbody></table></div>
            @else
                <div class="p-6"><x-empty-state title="No assessment data" message="Record grades for enrolled students to populate learning analytics." /></div>
            @endif
        </section>
    @else
        <section class="dashboard-panel mt-7 p-6"><x-empty-state title="No active subjects" message="An administrator must assign you an active subject before analytics are available." /></section>
    @endif
@endsection
