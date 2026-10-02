@extends('layouts.dashboard', ['title' => 'AI Tutor | EduPulse AI'])

@section('content')
    <div class="flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
        <div>
            <p class="text-sm font-semibold text-cyan-600">Grounded learning support</p>
            <h1 class="mt-1 text-3xl font-semibold tracking-tight text-slate-950">AI Tutor</h1>
            <p class="mt-2 text-sm text-slate-500">Ask questions about your subjects and see exactly which teacher materials supported the answer.</p>
        </div>
        @if ($selectedSubject)
            <div class="flex flex-wrap gap-2">
                @foreach ($subjects as $subject)
                    <a href="{{ route('student.tutor.index', ['subject' => $subject->id]) }}" class="rounded-full px-3.5 py-2 text-xs font-semibold ring-1 transition {{ $selectedSubject->is($subject) ? 'bg-slate-950 text-white ring-slate-950' : 'bg-white text-slate-600 ring-slate-200 hover:ring-cyan-300 hover:text-cyan-700' }}">{{ $subject->code }}</a>
                @endforeach
            </div>
        @endif
    </div>

    @if ($errors->any())
        <div class="mt-6 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800" role="alert"><p class="font-semibold">The tutor could not answer that question.</p><ul class="mt-2 list-disc space-y-1 pl-5 text-xs">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    @if ($selectedSubject)
        <section class="mt-7 grid min-h-[660px] gap-6 xl:grid-cols-[300px_minmax(0,1fr)]">
            <aside class="dashboard-panel overflow-hidden">
                <div class="border-b border-slate-100 p-5">
                    <a href="{{ route('student.tutor.index', ['subject' => $selectedSubject->id]) }}" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-cyan-600 px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-cyan-600/20 transition hover:bg-cyan-700"><span class="text-lg leading-none">+</span> New conversation</a>
                    <div class="mt-4 rounded-xl p-3 {{ $readyModuleCount > 0 ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">
                        <p class="text-xs font-semibold">{{ $readyModuleCount }} ready {{ Str::plural('module', $readyModuleCount) }}</p>
                        <p class="mt-1 text-[10px] leading-4 opacity-80">{{ $readyModuleCount > 0 ? 'Teacher materials are available to support tutor answers.' : 'Answers will be general until your teacher uploads a module.' }}</p>
                    </div>
                </div>
                <div class="max-h-[510px] overflow-y-auto p-3">
                    <p class="px-2 py-2 text-[10px] font-bold uppercase tracking-[0.16em] text-slate-400">Conversation history</p>
                    <div class="space-y-1">
                        @forelse ($conversations as $item)
                            <a href="{{ route('student.tutor.index', ['subject' => $selectedSubject->id, 'conversation' => $item->id]) }}" class="block rounded-xl px-3 py-3 transition {{ $conversation?->is($item) ? 'bg-indigo-50 text-indigo-800' : 'text-slate-600 hover:bg-slate-50' }}"><p class="line-clamp-2 text-sm font-medium">{{ $item->title }}</p><p class="mt-1 text-[10px] text-slate-400">{{ $item->updated_at->diffForHumans() }}</p></a>
                        @empty
                            <p class="px-3 py-5 text-xs leading-5 text-slate-400">Your conversations for {{ $selectedSubject->code }} will appear here.</p>
                        @endforelse
                    </div>
                </div>
            </aside>

            <article class="dashboard-panel flex min-h-[660px] flex-col overflow-hidden">
                <div class="flex items-center justify-between gap-4 border-b border-slate-100 px-5 py-4 sm:px-6">
                    <div><p class="text-xs font-bold uppercase tracking-[0.14em] text-indigo-600">{{ $selectedSubject->code }}</p><h2 class="mt-1 font-semibold text-slate-900">{{ $conversation?->title ?? 'Start a new conversation' }}</h2></div>
                    <span class="hidden rounded-full bg-slate-100 px-3 py-1.5 text-[10px] font-semibold text-slate-500 sm:inline-flex">Answers can be imperfect—verify important details</span>
                </div>

                <div class="flex-1 space-y-5 overflow-y-auto bg-slate-50/50 p-5 sm:p-6">
                    @if ($conversation)
                        @foreach ($conversation->messages as $message)
                            @if ($message->role === 'user')
                                <div class="ml-auto max-w-2xl rounded-2xl rounded-br-md bg-slate-950 px-5 py-4 text-sm leading-6 text-white shadow-sm">{{ $message->content }}</div>
                            @elseif ($message->role === 'assistant')
                                <div class="max-w-3xl">
                                    <div class="rounded-2xl rounded-bl-md border border-slate-200 bg-white px-5 py-4 text-sm leading-7 text-slate-700 shadow-sm"><p class="whitespace-pre-line">{{ $message->content }}</p></div>
                                </div>
                            @endif
                        @endforeach
                    @else
                        <div class="grid h-full min-h-80 place-items-center text-center">
                            <div class="max-w-md"><span class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-cyan-50 text-cyan-600"><x-icon name="bot" class="h-7 w-7" /></span><h2 class="mt-4 text-lg font-semibold text-slate-900">What would you like to understand?</h2><p class="mt-2 text-sm leading-6 text-slate-400">Ask about a concept, request an example, or get help breaking down a difficult step from {{ $selectedSubject->title }}.</p></div>
                        </div>
                    @endif
                </div>

                <form method="POST" action="{{ route('student.tutor.store') }}" class="border-t border-slate-100 bg-white p-4 sm:p-5">
                    @csrf
                    <input type="hidden" name="subject_id" value="{{ $selectedSubject->id }}" />
                    @if ($conversation)<input type="hidden" name="conversation_id" value="{{ $conversation->id }}" />@endif
                    <div class="flex items-end gap-3">
                        <label class="flex-1"><span class="sr-only">Question</span><textarea name="question" rows="2" maxlength="2000" required placeholder="Ask a question about {{ $selectedSubject->code }}..." class="block max-h-40 min-h-14 w-full resize-y rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm leading-6 outline-none placeholder:text-slate-400 focus:border-cyan-400 focus:bg-white focus:ring-4 focus:ring-cyan-100">{{ old('question') }}</textarea></label>
                        <button type="submit" class="inline-flex h-14 shrink-0 items-center justify-center gap-2 rounded-xl bg-indigo-600 px-5 text-sm font-semibold text-white shadow-lg shadow-indigo-600/20 transition hover:bg-indigo-700"><x-icon name="arrow" class="h-4 w-4" /><span class="hidden sm:inline">Ask tutor</span></button>
                    </div>
                    <p class="mt-2 text-[10px] text-slate-400">EduPulse retrieves only materials from this enrolled subject. Source cards show the passages used.</p>
                </form>
            </article>
        </section>
    @else
        <section class="dashboard-panel mt-7 p-6"><x-empty-state title="No active subjects" message="You must be actively enrolled in a subject before using the AI tutor." /></section>
    @endif
@endsection
