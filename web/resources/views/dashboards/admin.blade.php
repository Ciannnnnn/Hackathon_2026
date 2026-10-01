@extends('layouts.dashboard', ['title' => 'Admin Dashboard | EduPulse AI'])

@section('content')
    <div>
        <p class="text-sm font-semibold text-cyan-600">Administration</p>
        <h1 class="mt-1 text-3xl font-semibold tracking-tight text-slate-950">System overview</h1>
        <p class="mt-2 text-sm text-slate-500">Manage secure access, subject offerings, teaching assignments, and class enrollment.</p>
    </div>
    <section class="mt-7 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-metric-card label="All accounts" :value="$metrics['users']" detail="Users provisioned in EduPulse" icon="users" tone="indigo" />
        <x-metric-card label="Students" :value="$metrics['students']" detail="Learner accounts and profiles" icon="book" tone="cyan" />
        <x-metric-card label="Teachers" :value="$metrics['teachers']" detail="Instructor accounts and profiles" icon="chart" tone="emerald" />
        <x-metric-card label="Inactive" :value="$metrics['inactive']" detail="Accounts currently blocked from access" icon="logout" tone="rose" />
    </section>

    <section class="dashboard-panel mt-6 flex flex-col gap-5 p-6 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="font-semibold text-slate-900">Subject management</h2>
            <p class="mt-1 text-sm text-slate-500">Create subjects, assign teachers, and enroll students so academic data has a managed source.</p>
        </div>
        <a href="{{ route('admin.subjects.index') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-indigo-600/20 transition hover:bg-indigo-700">Manage subjects <x-icon name="arrow" class="h-4 w-4" /></a>
    </section>

    <section class="dashboard-panel mt-6 flex flex-col gap-5 p-6 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="font-semibold text-slate-900">Account management</h2>
            <p class="mt-1 text-sm text-slate-500">Create role-specific accounts and control application access.</p>
        </div>
        <a href="{{ route('admin.users.index') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-cyan-600 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-cyan-600/20 transition hover:bg-cyan-700">Manage users <x-icon name="arrow" class="h-4 w-4" /></a>
    </section>

    <section class="dashboard-panel mt-6 overflow-hidden">
        <div class="flex flex-col gap-5 p-6 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-start gap-4">
                <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-violet-50 text-violet-600"><x-icon name="sparkles" /></span>
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="font-semibold text-slate-900">Gemini generative AI</h2>
                        <span class="rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider {{ $ai['configured'] ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' : 'bg-amber-50 text-amber-700 ring-1 ring-amber-200' }}">{{ $ai['configured'] ? 'Configured' : 'Key required' }}</span>
                    </div>
                    <p class="mt-1 text-sm text-slate-500">{{ $ai['provider'] }} · {{ $ai['model'] }} · Demo fallback {{ $ai['fallback'] ? 'enabled' : 'disabled' }}</p>
                    @if (! $ai['configured'])
                        <p class="mt-2 text-xs text-amber-700">Add <code class="rounded bg-amber-50 px-1.5 py-0.5 font-mono">GEMINI_API_KEY</code> to <code class="rounded bg-slate-100 px-1.5 py-0.5 font-mono">web/.env</code>, then clear configuration cache.</p>
                    @endif
                </div>
            </div>
            <form method="POST" action="{{ route('admin.integrations.gemini.test') }}">
                @csrf
                <button type="submit" @disabled(! $ai['configured']) class="inline-flex items-center justify-center gap-2 rounded-xl px-5 py-3 text-sm font-semibold transition {{ $ai['configured'] ? 'bg-violet-600 text-white shadow-lg shadow-violet-600/20 hover:bg-violet-700' : 'cursor-not-allowed bg-slate-100 text-slate-400' }}"><x-icon name="sparkles" class="h-4 w-4" /> Test Gemini</button>
            </form>
        </div>
        @error('gemini')<p class="border-t border-rose-100 bg-rose-50 px-6 py-3 text-sm text-rose-700" role="alert">{{ $message }}</p>@enderror
    </section>
@endsection
