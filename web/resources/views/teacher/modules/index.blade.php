@extends('layouts.dashboard', ['title' => 'Learning Modules | EduPulse AI'])

@section('content')
    <div class="flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
        <div>
            <p class="text-sm font-semibold text-cyan-600">Teacher workspace</p>
            <h1 class="mt-1 text-3xl font-semibold tracking-tight text-slate-950">Learning modules</h1>
            <p class="mt-2 text-sm text-slate-500">Upload trusted PDF lessons and convert them into page-aware text for the grounded AI tutor.</p>
        </div>
        @if ($selectedSubject)
            <div class="flex flex-wrap gap-2">
                @foreach ($subjects as $subject)
                    <a href="{{ route('teacher.modules.index', ['subject' => $subject->id]) }}" class="rounded-full px-3.5 py-2 text-xs font-semibold ring-1 transition {{ $selectedSubject->is($subject) ? 'bg-slate-950 text-white ring-slate-950' : 'bg-white text-slate-600 ring-slate-200 hover:ring-cyan-300 hover:text-cyan-700' }}">{{ $subject->code }}</a>
                @endforeach
            </div>
        @endif
    </div>

    @if ($errors->any())
        <div class="mt-6 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800" role="alert">
            <p class="font-semibold">The module could not be processed.</p>
            <ul class="mt-2 list-disc space-y-1 pl-5 text-xs">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    @if ($selectedSubject)
        <section class="dashboard-panel mt-7 overflow-hidden">
            <div class="border-b border-slate-100 px-5 py-5 sm:px-6">
                <div class="flex items-start gap-3">
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-indigo-50 text-indigo-600"><x-icon name="book" class="h-5 w-5" /></span>
                    <div><h2 class="font-semibold text-slate-900">Upload a PDF for {{ $selectedSubject->code }}</h2><p class="mt-1 text-xs leading-5 text-slate-400">Text-based PDFs only, up to {{ config('services.rag.max_pdf_size_mb', 10) }} MB. Files remain private and are never exposed through public storage.</p></div>
                </div>
            </div>
            <form method="POST" action="{{ route('teacher.modules.store') }}" enctype="multipart/form-data" class="grid gap-5 p-5 sm:p-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.2fr)_auto] lg:items-end">
                @csrf
                <input type="hidden" name="subject_id" value="{{ $selectedSubject->id }}" />
                <label class="block"><span class="text-xs font-semibold text-slate-600">Module title</span><input name="title" value="{{ old('title') }}" maxlength="180" required placeholder="Module 1: Database Foundations" class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-3 text-sm outline-none placeholder:text-slate-400 focus:border-cyan-400 focus:bg-white focus:ring-4 focus:ring-cyan-100" /></label>
                <label class="block"><span class="text-xs font-semibold text-slate-600">PDF document</span><input type="file" name="pdf" accept="application/pdf,.pdf" required class="mt-2 block w-full rounded-xl border border-slate-200 bg-slate-50 text-sm text-slate-500 file:mr-4 file:border-0 file:bg-slate-950 file:px-4 file:py-3 file:text-sm file:font-semibold file:text-white hover:file:bg-slate-800" /></label>
                <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-indigo-600/20 transition hover:bg-indigo-700"><x-icon name="sparkles" class="h-4 w-4" /> Upload and extract</button>
            </form>
        </section>

        <section class="mt-6 grid gap-4 xl:grid-cols-2">
            @forelse ($modules as $module)
                @php
                    $statusStyle = match ($module->processing_status) {
                        'ready' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                        'failed' => 'bg-rose-50 text-rose-700 ring-rose-200',
                        'processing' => 'bg-cyan-50 text-cyan-700 ring-cyan-200',
                        default => 'bg-amber-50 text-amber-700 ring-amber-200',
                    };
                @endphp
                <article class="dashboard-panel flex flex-col overflow-hidden">
                    <div class="flex-1 p-5 sm:p-6">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0"><p class="text-[10px] font-bold uppercase tracking-[0.16em] text-indigo-600">{{ $module->subject->code }}</p><h2 class="mt-1 truncate font-semibold text-slate-900">{{ $module->title }}</h2><p class="mt-1 truncate text-xs text-slate-400">{{ $module->original_filename }}</p></div>
                            <span class="shrink-0 rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider ring-1 {{ $statusStyle }}">{{ $module->processing_status }}</span>
                        </div>
                        <div class="mt-5 grid grid-cols-3 gap-3 rounded-xl bg-slate-50 p-4 text-center">
                            <div><p class="text-sm font-semibold text-slate-800">{{ $module->chunks_count }}</p><p class="mt-0.5 text-[10px] uppercase tracking-wider text-slate-400">Chunks</p></div>
                            <div><p class="text-sm font-semibold text-slate-800">{{ number_format($module->file_size_bytes / 1024, 0) }} KB</p><p class="mt-0.5 text-[10px] uppercase tracking-wider text-slate-400">File size</p></div>
                            <div><p class="text-sm font-semibold text-slate-800">{{ $module->uploaded_at->format('M d') }}</p><p class="mt-0.5 text-[10px] uppercase tracking-wider text-slate-400">Uploaded</p></div>
                        </div>
                        @if ($module->processing_status === 'ready' && $module->firstChunk)
                            <div class="mt-4 rounded-xl border border-emerald-100 bg-emerald-50/50 p-4"><p class="text-[10px] font-bold uppercase tracking-wider text-emerald-700">Extracted preview · page {{ $module->firstChunk->page_number }}</p><p class="mt-2 line-clamp-3 text-xs leading-5 text-slate-500">{{ $module->firstChunk->content }}</p></div>
                        @elseif ($module->processing_status === 'failed')
                            <div class="mt-4 rounded-xl border border-rose-100 bg-rose-50 p-4"><p class="text-xs leading-5 text-rose-700">{{ $module->processing_error }}</p></div>
                        @endif
                    </div>
                    <div class="flex flex-wrap items-center gap-2 border-t border-slate-100 bg-slate-50/60 px-5 py-4 sm:px-6">
                        <a href="{{ route('teacher.modules.download', $module) }}" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 transition hover:border-cyan-200 hover:text-cyan-700">Download PDF</a>
                        @if ($module->processing_status === 'failed')
                            <form method="POST" action="{{ route('teacher.modules.retry', $module) }}">@csrf<button class="rounded-lg bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-700 transition hover:bg-indigo-100">Retry extraction</button></form>
                        @endif
                        <form method="POST" action="{{ route('teacher.modules.destroy', $module) }}" class="ml-auto" onsubmit="return confirm('Delete this module and all extracted text?')">@csrf @method('DELETE')<button class="rounded-lg bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 transition hover:bg-rose-100">Delete</button></form>
                    </div>
                </article>
            @empty
                <article class="dashboard-panel p-6 xl:col-span-2"><x-empty-state title="No learning modules yet" message="Upload the first PDF to prepare trusted course material for the Phase 10 AI tutor." /></article>
            @endforelse
        </section>
    @else
        <section class="dashboard-panel mt-7 p-6"><x-empty-state title="No active subjects assigned" message="An administrator must create and assign a subject before you can upload learning materials." /></section>
    @endif
@endsection
