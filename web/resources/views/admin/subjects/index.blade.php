@extends('layouts.dashboard', ['title' => 'Subjects | EduPulse AI'])

@section('content')
    <div class="flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
        <div>
            <p class="text-sm font-semibold text-cyan-600">Administration</p>
            <h1 class="mt-1 text-3xl font-semibold tracking-tight text-slate-950">Subjects and enrollments</h1>
            <p class="mt-2 text-sm text-slate-500">Create real subject offerings, assign their teacher, and choose which students belong to each class.</p>
        </div>
        <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center gap-2 self-start rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 shadow-sm transition hover:border-cyan-200 hover:text-cyan-700"><x-icon name="dashboard" class="h-4 w-4" /> Admin overview</a>
    </div>

    @if ($errors->any())
        <div class="mt-6 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800" role="alert">
            <p class="font-semibold">Please correct the subject information.</p>
            <ul class="mt-2 list-disc space-y-1 pl-5 text-xs">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <section class="mt-7 grid items-start gap-6 xl:grid-cols-[minmax(340px,0.8fr)_minmax(0,1.4fr)]">
        <article class="dashboard-panel overflow-hidden xl:sticky xl:top-28">
            <div class="border-b border-slate-100 px-5 py-5 sm:px-6">
                <h2 class="font-semibold text-slate-900">Add a subject</h2>
                <p class="mt-1 text-xs leading-5 text-slate-400">A subject must have an assigned teacher. Student enrollment can be changed later.</p>
            </div>
            <form method="POST" action="{{ route('admin.subjects.store') }}" class="space-y-5 p-5 sm:p-6">
                @csrf
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="block"><span class="text-xs font-semibold text-slate-600">Subject code</span><input name="code" value="{{ old('code') }}" maxlength="30" required placeholder="IT 301" class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-3 text-sm uppercase outline-none placeholder:normal-case placeholder:text-slate-400 focus:border-cyan-400 focus:bg-white focus:ring-4 focus:ring-cyan-100" /></label>
                    <label class="block"><span class="text-xs font-semibold text-slate-600">Term</span><input name="term" value="{{ old('term', '1st Semester') }}" maxlength="30" required class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-3 text-sm outline-none focus:border-cyan-400 focus:bg-white focus:ring-4 focus:ring-cyan-100" /></label>
                </div>
                <label class="block"><span class="text-xs font-semibold text-slate-600">Title</span><input name="title" value="{{ old('title') }}" maxlength="160" required placeholder="Database Management Systems" class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-3 text-sm outline-none placeholder:text-slate-400 focus:border-cyan-400 focus:bg-white focus:ring-4 focus:ring-cyan-100" /></label>
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="block"><span class="text-xs font-semibold text-slate-600">School year</span><input name="school_year" value="{{ old('school_year', now()->year.'-'.now()->addYear()->year) }}" maxlength="20" required class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-3 text-sm outline-none focus:border-cyan-400 focus:bg-white focus:ring-4 focus:ring-cyan-100" /></label>
                    <label class="block"><span class="text-xs font-semibold text-slate-600">Teacher</span><select name="teacher_id" required class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-3 text-sm outline-none focus:border-cyan-400 focus:bg-white focus:ring-4 focus:ring-cyan-100"><option value="">Select teacher</option>@foreach ($teachers as $teacher)<option value="{{ $teacher->id }}" @selected((string) old('teacher_id') === (string) $teacher->id)>{{ $teacher->user->full_name }}</option>@endforeach</select></label>
                </div>
                <label class="block"><span class="text-xs font-semibold text-slate-600">Description <span class="font-normal text-slate-400">(optional)</span></span><textarea name="description" maxlength="2000" rows="3" class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-3 text-sm outline-none focus:border-cyan-400 focus:bg-white focus:ring-4 focus:ring-cyan-100">{{ old('description') }}</textarea></label>

                <fieldset>
                    <legend class="text-xs font-semibold text-slate-600">Initial students <span class="font-normal text-slate-400">(optional)</span></legend>
                    <div class="mt-2 max-h-52 space-y-1 overflow-y-auto rounded-xl border border-slate-200 bg-slate-50 p-2">
                        @forelse ($students as $student)
                            <label class="flex cursor-pointer items-center gap-3 rounded-lg px-3 py-2.5 transition hover:bg-white"><input type="checkbox" name="student_ids[]" value="{{ $student->id }}" @checked(in_array($student->id, old('student_ids', []))) class="h-4 w-4 rounded border-slate-300 text-cyan-600 focus:ring-cyan-500" /><span class="min-w-0"><span class="block truncate text-sm font-medium text-slate-700">{{ $student->user->full_name }}</span><span class="block text-[10px] text-slate-400">{{ $student->student_number }} · {{ $student->grade_level }}</span></span></label>
                        @empty
                            <p class="p-3 text-xs text-slate-400">Create student accounts before enrolling a class.</p>
                        @endforelse
                    </div>
                </fieldset>
                <input type="hidden" name="is_active" value="0" />
                <label class="flex items-center gap-3 rounded-xl bg-slate-50 p-3.5"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', '1')) class="h-4 w-4 rounded border-slate-300 text-cyan-600 focus:ring-cyan-500" /><span class="text-sm font-medium text-slate-700">Make this subject active</span></label>
                <button type="submit" @disabled($teachers->isEmpty()) class="inline-flex w-full items-center justify-center gap-2 rounded-xl px-5 py-3 text-sm font-semibold text-white shadow-lg transition {{ $teachers->isEmpty() ? 'cursor-not-allowed bg-slate-300' : 'bg-cyan-600 shadow-cyan-600/20 hover:bg-cyan-700' }}"><x-icon name="check" class="h-4 w-4" /> Create subject</button>
            </form>
        </article>

        <div class="space-y-4">
            @forelse ($subjects as $subject)
                @php($activeStudentIds = $subject->students->where('pivot.status', 'active')->pluck('id')->all())
                <article class="dashboard-panel overflow-hidden">
                    <div class="flex flex-col gap-4 p-5 sm:flex-row sm:items-start sm:justify-between sm:p-6">
                        <div>
                            <div class="flex flex-wrap items-center gap-2"><h2 class="font-semibold text-slate-900">{{ $subject->code }} · {{ $subject->title }}</h2><span class="rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider {{ $subject->is_active ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' : 'bg-slate-100 text-slate-500 ring-1 ring-slate-200' }}">{{ $subject->is_active ? 'Active' : 'Inactive' }}</span></div>
                            <p class="mt-2 text-sm text-slate-500">{{ $subject->teacher->user->full_name }} · {{ $subject->school_year }} · {{ $subject->term }}</p>
                            <p class="mt-1 text-xs text-slate-400">{{ $subject->active_students_count }} active student(s)</p>
                        </div>
                        <form method="POST" action="{{ route('admin.subjects.status', $subject) }}">@csrf @method('PATCH')<button class="rounded-lg px-3 py-2 text-xs font-semibold transition {{ $subject->is_active ? 'bg-rose-50 text-rose-700 hover:bg-rose-100' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' }}">{{ $subject->is_active ? 'Deactivate' : 'Activate' }}</button></form>
                    </div>
                    <details class="border-t border-slate-100">
                        <summary class="cursor-pointer list-none px-5 py-4 text-sm font-semibold text-cyan-700 sm:px-6">Manage enrolled students</summary>
                        <form method="POST" action="{{ route('admin.subjects.enrollments.update', $subject) }}" class="border-t border-slate-100 bg-slate-50/60 p-5 sm:p-6">
                            @csrf @method('PUT')
                            <div class="grid max-h-64 gap-2 overflow-y-auto sm:grid-cols-2">
                                @forelse ($students as $student)
                                    <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 bg-white px-3 py-3"><input type="checkbox" name="student_ids[]" value="{{ $student->id }}" @checked(in_array($student->id, $activeStudentIds)) class="h-4 w-4 rounded border-slate-300 text-cyan-600 focus:ring-cyan-500" /><span class="min-w-0"><span class="block truncate text-sm font-medium text-slate-700">{{ $student->user->full_name }}</span><span class="block text-[10px] text-slate-400">{{ $student->student_number }}</span></span></label>
                                @empty
                                    <p class="text-xs text-slate-400">No student accounts are available.</p>
                                @endforelse
                            </div>
                            <button type="submit" class="mt-4 rounded-xl bg-slate-950 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">Save enrollments</button>
                        </form>
                    </details>
                </article>
            @empty
                <article class="dashboard-panel p-6"><x-empty-state title="No subjects yet" message="Use the form to create the first subject and assign its teacher and students." /></article>
            @endforelse
        </div>
    </section>
@endsection
