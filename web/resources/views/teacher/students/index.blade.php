@extends('layouts.dashboard', ['title' => 'Students | EduPulse AI'])

@section('content')
    <div class="flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
        <div>
            <p class="text-sm font-semibold text-cyan-600">Teacher workspace</p>
            <h1 class="mt-1 text-3xl font-semibold tracking-tight text-slate-950">Students</h1>
            <p class="mt-2 text-sm text-slate-500">Find learners, review their latest indicators, and open a complete academic analysis.</p>
        </div>
        <a href="{{ route('teacher.dashboard') }}" class="inline-flex items-center gap-2 self-start rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 shadow-sm transition hover:border-cyan-200 hover:text-cyan-700">
            <x-icon name="dashboard" class="h-4 w-4" /> Dashboard overview
        </a>
    </div>

    <section class="mt-7 grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Student roster summary">
        <x-metric-card label="Visible students" :value="$summary['visible']" detail="Learners matching the current filters" icon="users" tone="indigo" />
        <x-metric-card label="High support" :value="$summary['high']" detail="Learners needing prompt follow-up" icon="sparkles" tone="rose" />
        <x-metric-card label="Moderate support" :value="$summary['moderate']" detail="Learners needing targeted practice" icon="trend" tone="amber" />
        <x-metric-card label="Unassessed" :value="$summary['unassessed']" detail="Learners without a performance snapshot" icon="calendar" tone="cyan" />
    </section>

    <section class="dashboard-panel mt-6 p-5 sm:p-6">
        <form method="GET" action="{{ route('teacher.students.index') }}" class="grid gap-4 lg:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)_minmax(0,1fr)_auto] lg:items-end">
            <label class="block">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Search</span>
                <input name="search" value="{{ $filters['search'] }}" maxlength="100" placeholder="Name, student number, or email" class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-3 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-cyan-400 focus:bg-white focus:ring-4 focus:ring-cyan-100" />
            </label>
            <label class="block">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Subject</span>
                <select name="subject" class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-3 text-sm text-slate-700 outline-none transition focus:border-cyan-400 focus:bg-white focus:ring-4 focus:ring-cyan-100">
                    <option value="">All subjects</option>
                    @foreach ($subjects as $subject)
                        <option value="{{ $subject->id }}" @selected($filters['subject'] === $subject->id)>{{ $subject->code }} — {{ $subject->title }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Support level</span>
                <select name="support" class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-3 text-sm text-slate-700 outline-none transition focus:border-cyan-400 focus:bg-white focus:ring-4 focus:ring-cyan-100">
                    <option value="">All levels</option>
                    @foreach (['HIGH' => 'High', 'MODERATE' => 'Moderate', 'LOW' => 'Low', 'UNASSESSED' => 'Unassessed'] as $value => $label)
                        <option value="{{ $value }}" @selected($filters['support'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <div class="flex gap-2">
                <button class="inline-flex flex-1 items-center justify-center rounded-xl bg-slate-950 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-cyan-700 lg:flex-none">Apply</button>
                @if ($filters['search'] || $filters['subject'] || $filters['support'])
                    <a href="{{ route('teacher.students.index') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-500 transition hover:bg-slate-50">Clear</a>
                @endif
            </div>
        </form>
    </section>

    <section class="dashboard-panel mt-6 overflow-hidden">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-5 sm:px-6">
            <div>
                <h2 class="font-semibold text-slate-900">Student academic pulse</h2>
                <p class="mt-1 text-xs text-slate-400">High-support learners are shown first</p>
            </div>
            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-500">{{ $students->count() }} results</span>
        </div>

        @if ($students->isNotEmpty())
            <div class="overflow-x-auto">
                <table class="w-full min-w-[1100px] text-left">
                    <thead class="bg-slate-50/80 text-[10px] font-bold uppercase tracking-[0.12em] text-slate-400">
                        <tr>
                            <th class="px-6 py-3.5">Student</th>
                            <th class="px-4 py-3.5">Subject</th>
                            <th class="px-4 py-3.5">Attendance</th>
                            <th class="px-4 py-3.5">Quiz</th>
                            <th class="px-4 py-3.5">Assignment</th>
                            <th class="px-4 py-3.5">Activity</th>
                            <th class="px-4 py-3.5">Trend</th>
                            <th class="px-4 py-3.5">Support</th>
                            <th class="px-6 py-3.5"><span class="sr-only">Action</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($students as $student)
                            <tr class="group transition hover:bg-cyan-50/30">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-gradient-to-br from-cyan-100 to-indigo-100 text-xs font-bold text-indigo-700">
                                            {{ collect(explode(' ', $student['name']))->map(fn ($part) => substr($part, 0, 1))->take(2)->join('') }}
                                        </span>
                                        <div>
                                            <a href="{{ route('teacher.students.show', ['student' => $student['id'], 'subject' => $filters['subject']]) }}" class="text-sm font-semibold text-slate-800 transition hover:text-cyan-700">{{ $student['name'] }}</a>
                                            <p class="mt-0.5 text-xs text-slate-400">{{ $student['student_number'] }} · {{ $student['grade_level'] }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-sm font-medium text-slate-600">{{ $student['subject_code'] ?? '—' }}</td>
                                @foreach (['attendance', 'quiz_average', 'assignment_average', 'activity_score'] as $metric)
                                    <td class="px-4 py-4 text-sm font-medium text-slate-600">{{ $student[$metric] !== null ? number_format($student[$metric], 0).'%' : '—' }}</td>
                                @endforeach
                                <td class="px-4 py-4">
                                    @php($trendTone = $student['trend_label'] === 'Improving' ? 'text-emerald-600 bg-emerald-50' : ($student['trend_label'] === 'Declining' ? 'text-rose-600 bg-rose-50' : 'text-slate-600 bg-slate-100'))
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $trendTone }}">{{ $student['trend_label'] }}</span>
                                </td>
                                <td class="px-4 py-4"><x-support-badge :level="$student['support_level']" /></td>
                                <td class="px-6 py-4 text-right">
                                    <a href="{{ route('teacher.students.show', ['student' => $student['id'], 'subject' => $filters['subject']]) }}" class="inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-xs font-semibold text-cyan-700 transition hover:bg-cyan-50">Analyze <x-icon name="arrow" class="h-3.5 w-3.5" /></a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="p-6"><x-empty-state title="No students found" message="Try clearing the filters, or enroll students in one of your active subjects." /></div>
        @endif
    </section>
@endsection
