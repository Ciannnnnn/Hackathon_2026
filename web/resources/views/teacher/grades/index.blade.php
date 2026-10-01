@extends('layouts.dashboard', ['title' => 'Gradebook | EduPulse AI'])

@section('content')
    <div class="flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
        <div>
            <p class="text-sm font-semibold text-cyan-600">Teacher workspace</p>
            <h1 class="mt-1 text-3xl font-semibold tracking-tight text-slate-950">Gradebook</h1>
            <p class="mt-2 text-sm text-slate-500">Record verified class grades and academic indicators for students enrolled in your subjects.</p>
        </div>
        @if ($selectedSubject)
            <div class="flex flex-wrap gap-2">
                @foreach ($subjects as $subject)
                    <a href="{{ route('teacher.grades.index', ['subject' => $subject->id]) }}" class="rounded-full px-3.5 py-2 text-xs font-semibold ring-1 transition {{ $selectedSubject->is($subject) ? 'bg-slate-950 text-white ring-slate-950' : 'bg-white text-slate-600 ring-slate-200 hover:ring-cyan-300 hover:text-cyan-700' }}">{{ $subject->code }}</a>
                @endforeach
            </div>
        @endif
    </div>

    @if ($errors->any())
        <div class="mt-6 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800" role="alert"><p class="font-semibold">The grade could not be saved.</p><ul class="mt-2 list-disc space-y-1 pl-5 text-xs">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    @if ($selectedSubject)
        <section class="mt-7 rounded-2xl border border-cyan-100 bg-cyan-50/60 px-5 py-4">
            <p class="font-semibold text-slate-800">{{ $selectedSubject->code }} · {{ $selectedSubject->title }}</p>
            <p class="mt-1 text-xs text-slate-500">Overall grade is the equal average of quiz, assignment, and activity scores. Trend is calculated automatically against the preceding dated entry.</p>
        </section>

        <div class="mt-6 space-y-4">
            @forelse ($students as $student)
                @php($latest = $student->latestPerformance)
                <article class="dashboard-panel overflow-hidden">
                    <div class="flex flex-col gap-4 border-b border-slate-100 p-5 sm:flex-row sm:items-center sm:justify-between sm:p-6">
                        <div class="flex items-center gap-3">
                            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-indigo-50 text-sm font-bold text-indigo-700">{{ strtoupper(substr($student->user->first_name, 0, 1).substr($student->user->last_name, 0, 1)) }}</span>
                            <div><h2 class="font-semibold text-slate-800">{{ $student->user->full_name }}</h2><p class="mt-0.5 text-xs text-slate-400">{{ $student->student_number }} · {{ $student->grade_level }}</p></div>
                        </div>
                        <div class="flex items-center gap-3"><div class="text-right"><p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Current overall grade</p><p class="mt-1 text-xl font-semibold {{ $latest && $latest->overall_grade < 75 ? 'text-rose-600' : 'text-emerald-600' }}">{{ $latest ? number_format($latest->overall_grade, 1).'%' : 'Not graded' }}</p></div><a href="{{ route('teacher.students.show', ['student' => $student, 'subject' => $selectedSubject->id]) }}" class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold text-cyan-700 transition hover:bg-cyan-50">Analysis</a></div>
                    </div>
                    <form method="POST" action="{{ route('teacher.grades.store', $student) }}" class="p-5 sm:p-6">
                        @csrf
                        <input type="hidden" name="subject_id" value="{{ $selectedSubject->id }}" />
                        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            <label class="block"><span class="text-xs font-semibold text-slate-600">Grade date</span><input type="date" name="snapshot_date" max="{{ now()->toDateString() }}" value="{{ now()->toDateString() }}" required class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm outline-none focus:border-cyan-400 focus:bg-white focus:ring-4 focus:ring-cyan-100" /></label>
                            @foreach (['quiz_average' => 'Quiz grade', 'assignment_average' => 'Assignment grade', 'activity_score' => 'Activity grade', 'attendance_rate' => 'Attendance'] as $name => $label)
                                <label class="block"><span class="text-xs font-semibold text-slate-600">{{ $label }} (%)</span><input type="number" name="{{ $name }}" min="0" max="100" step="0.01" value="{{ $latest?->{$name} }}" required class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm outline-none focus:border-cyan-400 focus:bg-white focus:ring-4 focus:ring-cyan-100" /></label>
                            @endforeach
                            <label class="block"><span class="text-xs font-semibold text-slate-600">Late submissions</span><input type="number" name="late_submissions" min="0" max="100" value="{{ $latest?->late_submissions ?? 0 }}" required class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm outline-none focus:border-cyan-400 focus:bg-white focus:ring-4 focus:ring-cyan-100" /></label>
                            <label class="block"><span class="text-xs font-semibold text-slate-600">Missing submissions</span><input type="number" name="missing_submissions" min="0" max="100" value="{{ $latest?->missing_submissions ?? 0 }}" required class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm outline-none focus:border-cyan-400 focus:bg-white focus:ring-4 focus:ring-cyan-100" /></label>
                            <label class="block sm:col-span-2"><span class="text-xs font-semibold text-slate-600">Weak topics <span class="font-normal text-slate-400">(comma-separated)</span></span><input name="weak_topics" maxlength="500" placeholder="Normalization, SQL joins" class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm outline-none placeholder:text-slate-400 focus:border-cyan-400 focus:bg-white focus:ring-4 focus:ring-cyan-100" /></label>
                        </div>
                        <div class="mt-5 flex justify-end border-t border-slate-100 pt-5"><button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-cyan-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-cyan-600/20 transition hover:bg-cyan-700"><x-icon name="check" class="h-4 w-4" /> Save grades</button></div>
                    </form>
                </article>
            @empty
                <article class="dashboard-panel p-6"><x-empty-state title="No students enrolled" message="Ask an administrator to enroll students in this subject before recording grades." /></article>
            @endforelse
        </div>
    @else
        <section class="dashboard-panel mt-7 p-6"><x-empty-state title="No active subjects assigned" message="An administrator must create a subject and assign it to your teacher account." /></section>
    @endif
@endsection
