@extends('layouts.dashboard', ['title' => 'Quiz Results | EduPulse AI'])

@section('content')
    @php($percentage = (float) $attempt->max_score > 0 ? ((float) $attempt->score / (float) $attempt->max_score) * 100 : 0)
    <a href="{{ route('student.quizzes.index', ['subject' => $attempt->quiz->subject_id]) }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">← Back to quizzes</a>
    <section class="dashboard-panel mt-5 overflow-hidden">
        <div class="grid gap-6 p-6 sm:grid-cols-[auto_1fr] sm:items-center">
            <div class="grid h-28 w-28 place-items-center rounded-full bg-gradient-to-br from-cyan-50 to-indigo-100 text-center"><div><p class="text-3xl font-bold text-indigo-700">{{ number_format($percentage, 0) }}%</p><p class="text-[10px] uppercase tracking-wider text-indigo-500">{{ number_format((float) $attempt->score, 0) }}/{{ number_format((float) $attempt->max_score, 0) }}</p></div></div>
            <div><p class="text-xs font-bold uppercase tracking-wider text-cyan-600">{{ $attempt->quiz->subject->code }} · Results</p><h1 class="mt-2 text-2xl font-semibold text-slate-950">{{ $attempt->quiz->title }}</h1><p class="mt-2 text-sm leading-6 text-slate-500">{{ $attempt->recommended_review }}</p><p class="mt-3 text-xs text-slate-400">Completed {{ $attempt->completed_at?->format('M d, Y · g:i A') }}</p></div>
        </div>
    </section>

    <section class="mt-6 space-y-4">
        @foreach ($attempt->answers as $answer)
            <article class="dashboard-panel border-l-4 p-5 sm:p-6 {{ $answer->is_correct ? 'border-l-emerald-400' : 'border-l-rose-400' }}">
                <div class="flex items-start justify-between gap-4"><p class="text-sm font-semibold leading-6 text-slate-800">{{ $answer->question->position }}. {{ $answer->question->question_text }}</p><span class="shrink-0 rounded-full px-2.5 py-1 text-[10px] font-bold uppercase {{ $answer->is_correct ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">{{ $answer->is_correct ? 'Correct' : 'Review' }}</span></div>
                <div class="mt-4 grid gap-3 text-xs sm:grid-cols-2"><div class="rounded-xl bg-slate-50 p-3"><p class="font-semibold text-slate-400">Your answer</p><p class="mt-1 text-slate-700">{{ $answer->answer_text !== '' ? $answer->answer_text : 'No answer' }}</p></div><div class="rounded-xl bg-emerald-50 p-3"><p class="font-semibold text-emerald-600">Correct answer</p><p class="mt-1 text-emerald-800">{{ $answer->question->correct_answer }}</p></div></div>
                <p class="mt-4 text-xs leading-5 text-slate-500">{{ $answer->feedback }}</p>
                @if ($answer->question->sourceChunk)<p class="mt-3 text-[10px] font-semibold uppercase tracking-wider text-indigo-500">Review {{ $answer->question->sourceChunk->module?->title }}, page {{ $answer->question->sourceChunk->page_number }}</p>@endif
            </article>
        @endforeach
    </section>
@endsection
