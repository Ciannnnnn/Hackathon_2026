@extends('layouts.dashboard', ['title' => $quiz->title.' | EduPulse AI'])

@section('content')
    <a href="{{ route('student.quizzes.index', ['subject' => $quiz->subject_id]) }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">← Back to quizzes</a>
    <div class="mt-4"><p class="text-sm font-semibold text-cyan-600">{{ $quiz->subject->code }} · {{ ucfirst($quiz->difficulty) }}</p><h1 class="mt-1 text-3xl font-semibold tracking-tight text-slate-950">{{ $quiz->title }}</h1><p class="mt-2 text-sm text-slate-500">{{ $quiz->topic }} · {{ $quiz->question_count }} questions · Source: {{ $quiz->module?->title }}</p></div>

    @if ($errors->any())<div class="mt-6 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">Please answer the quiz using valid responses.</div>@endif

    <form method="POST" action="{{ route('student.quizzes.submit', $quiz) }}" class="mt-7 space-y-4">
        @csrf
        @foreach ($quiz->questions as $question)
            <fieldset class="dashboard-panel p-5 sm:p-6">
                <legend class="sr-only">Question {{ $question->position }}</legend>
                <div class="flex gap-3"><span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-indigo-50 text-xs font-bold text-indigo-700">{{ $question->position }}</span><p class="pt-1 text-sm font-semibold leading-6 text-slate-800">{{ $question->question_text }}</p></div>
                @if ($question->question_type === 'short_answer')
                    <input name="answers[{{ $question->id }}]" value="{{ old('answers.'.$question->id) }}" maxlength="1000" placeholder="Type your answer" class="mt-4 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-cyan-400 focus:bg-white" />
                @else
                    <div class="mt-4 grid gap-2 sm:grid-cols-2">@foreach ($question->choices as $choice)<label class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 hover:border-cyan-300"><input type="radio" name="answers[{{ $question->id }}]" value="{{ $choice }}" @checked(old('answers.'.$question->id) === $choice) class="text-indigo-600" /><span>{{ $choice }}</span></label>@endforeach</div>
                @endif
            </fieldset>
        @endforeach
        <div class="flex justify-end"><button type="submit" class="rounded-xl bg-indigo-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-indigo-600/20 hover:bg-indigo-700">Submit and grade</button></div>
    </form>
@endsection
