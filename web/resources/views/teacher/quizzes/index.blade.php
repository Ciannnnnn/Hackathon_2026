@extends('layouts.dashboard', ['title' => 'AI Quizzes | EduPulse AI'])

@section('content')
    <div class="flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
        <div>
            <p class="text-sm font-semibold text-cyan-600">Teacher workspace</p>
            <h1 class="mt-1 text-3xl font-semibold tracking-tight text-slate-950">AI-generated quizzes</h1>
            <p class="mt-2 text-sm text-slate-500">Generate review questions strictly from your extracted learning modules, inspect them, then publish for students.</p>
        </div>
        @if ($selectedSubject)
            <div class="flex flex-wrap gap-2">
                @foreach ($subjects as $subject)
                    <a href="{{ route('teacher.quizzes.index', ['subject' => $subject->id]) }}" class="rounded-full px-3.5 py-2 text-xs font-semibold ring-1 transition {{ $selectedSubject->is($subject) ? 'bg-slate-950 text-white ring-slate-950' : 'bg-white text-slate-600 ring-slate-200 hover:ring-cyan-300 hover:text-cyan-700' }}">{{ $subject->code }}</a>
                @endforeach
            </div>
        @endif
    </div>

    @if (session('status'))
        <div class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800" role="status">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="mt-6 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800" role="alert">
            <p class="font-semibold">The quiz could not be generated.</p>
            <ul class="mt-2 list-disc space-y-1 pl-5 text-xs">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    @if ($selectedSubject)
        <section class="dashboard-panel mt-7 overflow-hidden">
            <div class="border-b border-slate-100 px-5 py-5 sm:px-6">
                <h2 class="font-semibold text-slate-900">Create a grounded quiz for {{ $selectedSubject->code }}</h2>
                <p class="mt-1 text-xs leading-5 text-slate-400">Only ready modules with extracted text are available. Generation can take a few seconds.</p>
            </div>
            @if ($modules->isNotEmpty())
                <form method="POST" action="{{ route('teacher.quizzes.store') }}" data-loading-form data-loading-text="Generating quiz…" class="grid gap-5 p-5 sm:grid-cols-2 sm:p-6 xl:grid-cols-6">
                    @csrf
                    <input type="hidden" name="subject_id" value="{{ $selectedSubject->id }}" />
                    <label class="block sm:col-span-2 xl:col-span-2"><span class="text-xs font-semibold text-slate-600">Source module</span><select name="module_id" required class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-3 text-sm outline-none focus:border-cyan-400 focus:bg-white">@foreach ($modules as $module)<option value="{{ $module->id }}" @selected(old('module_id') == $module->id)>{{ $module->title }} ({{ $module->chunks_count }} chunks)</option>@endforeach</select></label>
                    <label class="block sm:col-span-2 xl:col-span-2"><span class="text-xs font-semibold text-slate-600">Quiz title</span><input name="title" value="{{ old('title') }}" maxlength="180" required placeholder="Module review quiz" class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-3 text-sm outline-none focus:border-cyan-400 focus:bg-white" /></label>
                    <label class="block xl:col-span-2"><span class="text-xs font-semibold text-slate-600">Topic</span><input name="topic" value="{{ old('topic') }}" maxlength="160" required placeholder="Core concepts" class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-3 text-sm outline-none focus:border-cyan-400 focus:bg-white" /></label>
                    <label class="block"><span class="text-xs font-semibold text-slate-600">Difficulty</span><select name="difficulty" class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-3 text-sm"><option value="easy">Easy</option><option value="medium" @selected(old('difficulty', 'medium') === 'medium')>Medium</option><option value="hard" @selected(old('difficulty') === 'hard')>Hard</option></select></label>
                    <label class="block"><span class="text-xs font-semibold text-slate-600">Questions</span><input type="number" name="question_count" value="{{ old('question_count', 5) }}" min="3" max="10" required class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-3 text-sm" /></label>
                    <label class="flex items-center gap-3 self-end rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-600"><input type="hidden" name="is_published" value="0" /><input type="checkbox" name="is_published" value="1" @checked(old('is_published')) class="rounded border-slate-300 text-indigo-600" /> Publish immediately</label>
                    <button type="submit" data-loading-button class="self-end rounded-xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-indigo-600/20 transition hover:bg-indigo-700 disabled:cursor-wait disabled:opacity-70">Generate quiz</button>
                </form>
            @else
                <div class="p-6"><x-empty-state title="No ready module" message="Upload and successfully extract a PDF learning module before generating a grounded quiz." /><div class="mt-4 text-center"><a href="{{ route('teacher.modules.index', ['subject' => $selectedSubject->id]) }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-800">Go to Learning Modules</a></div></div>
            @endif
        </section>

        <section class="mt-6 space-y-4">
            @forelse ($quizzes as $quiz)
                <article class="dashboard-panel overflow-hidden">
                    <div class="flex flex-col gap-4 p-5 sm:flex-row sm:items-start sm:justify-between sm:p-6">
                        <div><div class="flex flex-wrap items-center gap-2"><span class="rounded-full bg-indigo-50 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-indigo-700">{{ $quiz->difficulty }}</span><span class="rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider {{ $quiz->is_published ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">{{ $quiz->is_published ? 'Published' : 'Draft' }}</span></div><h2 class="mt-3 text-lg font-semibold text-slate-900">{{ $quiz->title }}</h2><p class="mt-1 text-xs text-slate-400">{{ $quiz->topic }} · {{ $quiz->module?->title }} · {{ $quiz->question_count }} questions · {{ $quiz->attempts_count }} attempts</p></div>
                        <form method="POST" action="{{ route('teacher.quizzes.publish', $quiz) }}">@csrf @method('PATCH')<button class="rounded-xl px-4 py-2.5 text-xs font-semibold {{ $quiz->is_published ? 'bg-amber-50 text-amber-800 hover:bg-amber-100' : 'bg-emerald-600 text-white hover:bg-emerald-700' }}">{{ $quiz->is_published ? 'Return to draft' : 'Publish quiz' }}</button></form>
                    </div>
                    <details class="border-t border-slate-100 bg-slate-50/60 px-5 py-4 sm:px-6">
                        <summary class="cursor-pointer text-xs font-semibold text-slate-600">Review questions and answer key</summary>
                        <ol class="mt-4 space-y-4">
                            @foreach ($quiz->questions as $question)
                                <li class="rounded-xl border border-slate-200 bg-white p-4 text-sm"><p class="font-medium text-slate-800">{{ $question->position }}. {{ $question->question_text }}</p>@if ($question->choices)<p class="mt-2 text-xs text-slate-500">Choices: {{ implode(' · ', $question->choices) }}</p>@endif<p class="mt-3 text-xs font-semibold text-emerald-700">Answer: {{ $question->correct_answer }}</p><p class="mt-1 text-xs leading-5 text-slate-500">{{ $question->explanation }}</p><p class="mt-2 text-[10px] uppercase tracking-wider text-slate-400">Grounded in page {{ $question->sourceChunk?->page_number ?? 'unknown' }}</p></li>
                            @endforeach
                        </ol>
                    </details>
                </article>
            @empty
                <article class="dashboard-panel p-6"><x-empty-state title="No quizzes yet" message="Generate your first quiz from a ready module above." /></article>
            @endforelse
        </section>
    @else
        <section class="dashboard-panel mt-7 p-6"><x-empty-state title="No active subjects assigned" message="An administrator must assign a subject before you can generate quizzes." /></section>
    @endif
@endsection
