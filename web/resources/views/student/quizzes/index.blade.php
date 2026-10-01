@extends('layouts.dashboard', ['title' => 'Practice Quizzes | EduPulse AI'])

@section('content')
    <div class="flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
        <div><p class="text-sm font-semibold text-cyan-600">Student workspace</p><h1 class="mt-1 text-3xl font-semibold tracking-tight text-slate-950">Practice quizzes</h1><p class="mt-2 text-sm text-slate-500">Test your understanding with quizzes grounded in your teacher's learning modules.</p></div>
        @if ($selectedSubject)<div class="flex flex-wrap gap-2">@foreach ($subjects as $subject)<a href="{{ route('student.quizzes.index', ['subject' => $subject->id]) }}" class="rounded-full px-3.5 py-2 text-xs font-semibold ring-1 transition {{ $selectedSubject->is($subject) ? 'bg-slate-950 text-white ring-slate-950' : 'bg-white text-slate-600 ring-slate-200 hover:ring-cyan-300 hover:text-cyan-700' }}">{{ $subject->code }}</a>@endforeach</div>@endif
    </div>

    @if (session('status'))<div class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">{{ session('status') }}</div>@endif

    <section class="mt-7 grid gap-4 lg:grid-cols-2">
        @forelse ($quizzes as $quiz)
            @php
                $latestAttempt = $quiz->attempts->first();
                $latestPercentage = $latestAttempt && (float) $latestAttempt->max_score > 0
                    ? ((float) $latestAttempt->score / (float) $latestAttempt->max_score) * 100
                    : null;
            @endphp
            <article class="dashboard-panel flex flex-col p-5 sm:p-6">
                <div class="flex items-start justify-between gap-4"><div><p class="text-[10px] font-bold uppercase tracking-[0.16em] text-indigo-600">{{ $quiz->difficulty }} · {{ $quiz->question_count }} questions</p><h2 class="mt-2 text-lg font-semibold text-slate-900">{{ $quiz->title }}</h2><p class="mt-1 text-xs text-slate-400">{{ $quiz->topic }} · {{ $quiz->module?->title }}</p></div>@if ($latestPercentage !== null)<div class="rounded-xl bg-cyan-50 px-3 py-2 text-center"><p class="text-lg font-bold text-cyan-700">{{ number_format($latestPercentage, 0) }}%</p><p class="text-[9px] uppercase tracking-wider text-cyan-600">Latest</p></div>@endif</div>
                <div class="mt-5 flex items-center justify-between border-t border-slate-100 pt-4"><p class="text-xs text-slate-400">{{ $quiz->attempts->count() }} {{ Str::plural('attempt', $quiz->attempts->count()) }}</p><a href="{{ route('student.quizzes.show', $quiz) }}" class="rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-semibold text-white hover:bg-indigo-700">{{ $latestAttempt ? 'Retake quiz' : 'Start quiz' }}</a></div>
            </article>
        @empty
            <article class="dashboard-panel p-6 lg:col-span-2"><x-empty-state title="No published quizzes" message="Your teacher has not published a quiz for this subject yet." /></article>
        @endforelse
    </section>
@endsection
